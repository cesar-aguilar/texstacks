<?php

namespace TexStacks\Parsers;

use TexStacks\Helpers\StrHelper;
use TexStacks\Parsers\Token;

class LatexLexer
{

  const ENVS_POST_OPTIONS = [
    'itemize',
    'enumerate',
    'compactenum',
    'compactitem',
    'asparaenum',
    'figure',
    'table',
    'center',
    'verbatim-environment',
    'proof',
  ];

  const DISPLAY_MATH_ENVIRONMENTS = [
    'equation',
    'equation*',
    'align',
    'align*',
    'multline',
    'multline*',
    'gather',
    'gather*',
    'flalign',
    'flalign*',
    'eqnarray',
    'eqnarray*',
  ];

  const SECTION_COMMANDS = [
    'part',
    'chapter', 'chapter\*',
    'section', 'section\*',
    'subsection', 'subsection\*',
    'subsubsection', 'subsubsection\*',
    'paragraph', 'paragraph\*',
    'subparagraph', 'subparagraph\*',
  ];

  const CMD_WITH_OPTIONS = [
    'item',
  ];

  const ONE_ARGS_CMDS_PRE_OPTIONS = [
    'includegraphics',
    'caption',
    'bibitem',
    'cite',
  ];

  const ONE_ARGS_CMDS = [
    'label',
    'ref',
    'eqref',
    'tag',
  ];

  const TABULAR_ENVIRONMENTS = [
    'tabular',
    'supertabular',
    'array',
  ];

  const FONT_COMMANDS = [
    'textrm',
    'textsf',
    'texttt',
    'textmd',
    'textbf',
    'textup',
    'textit',
    'textsl',
    'textsc',
    'emph',
    'textnormal',
    'textsuperscript',
    'textsubscript',
    'footnote',
  ];

  const FONT_DECLARATIONS = [
    'rm' => 'font-serif',
    'sl' => 'italic',
    'sc' => 'small-caps',
    'it' => 'italic',
    'tt' => 'font-mono',
    'bf' => 'font-bold',
    'bfseries' => 'font-bold',
    'mdseries' => 'font-medium',
    'rmfamily' => 'font-serif',
    'sffamily' => 'italic',
    'ttfamily' => 'font-mono',
    'upshape' => 'non-italic',
    'itshape' => 'italic',
    'scshape' => 'small-caps',
    'slshape' => 'italic',
    'em' => 'font-bold',
    'normalfont' => 'font-serif',
    'tiny' => 'text-xs',
    'scriptsize' => 'text-xs',
    'footnotesize' => 'text-sm',
    'small' => 'text-sm',
    'normalsize' => 'text-base',
    'large' => 'text-lg',
    'Large' => 'text-xl',
    'LARGE' => 'text-2xl',
    'huge' => 'text-3xl',
    'Huge' => 'text-4xl',
  ];

  const LIST_ENVIRONMENTS = [
    'itemize',
    'enumerate',
    'compactenum',
    'compactitem',
    'asparaenum',
  ];

  private array $tokens;
  private string $buffer;
  private int $line_number;
  private string $stream;
  private int $cursor;
  private string|null $prev_char;
  private int $num_chars;

  private string $command_name;
  private static array $thm_env = [];
  private static array $ref_labels;
  private static array $citations;

  public function __construct($data = [])
  {
    if (isset($data['thm_env'])) self::setTheoremEnvs($data['thm_env']);
  }

  public static function setRefLabels($labels)
  {
    self::$ref_labels = $labels;
  }

  public static function setTheoremEnvs($thm_envs)
  {
    self::$thm_env = array_unique([...self::$thm_env, ...$thm_envs]);
  }

  public static function setCitations($citations)
  {
    self::$citations = $citations;
  }

  public function tokenize(string $latex_src)
  {

    $this->line_number = 0;

    $this->num_chars = 0;

    $this->tokens = [];

    $this->buffer = '';

    $this->stream = $this->preprocessLatexSource($latex_src);

    $this->num_chars = strlen($this->stream);

    if ($this->num_chars === 0) return [];

    $this->cursor = -1;

    while (!is_null($char = $this->getNextChar())) {

      if ($char === '~') {
        $this->buffer .= ' ';
        continue;
      }

      if ($char === '{' || $char === '}') {
        $this->addGroupEnvToken($char);
        continue;
      }

      if ($char === '%') {
        $this->consumeUntilTarget("\n");
        continue;
      }

      if ($char !== "\\") {
        $this->buffer .= $char;
        continue;
      }

      $char = $this->getNextChar();

      // If char is non-alphabetic then we have a control symbol
      if (!ctype_alpha($char ?? '')) {

        if ($char === '(' || $char === ')') {
          $this->addInlineMathToken($char);
          continue;
        }

        $this->command_name = "\\";

        // $this->buffer .= "\\" . $char;
        $this->addSymbolToken($char);
        continue;
      }

      $this->command_name = $this->consumeUntilNonAlpha();

      // Make token
      if ($this->getCommandType($this->command_name) === 'section-cmd') {
        try {
          $token = $this->tokenizeSection();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage() . " at line {$this->line_number}");
        }

        $this->addToken($token);
      }
      else if ($this->getCommandType($this->command_name) === 'environment')
      {

        try {
          $env = $this->getEnvName();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        if ($this->command_name === 'end') {

          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_src' => "\\end{" . $env . "}",
            'line_number' => $this->line_number,
          ]));
        } 
        else if ($this->getCommandType($this->command_name, $env) === 'displaymath-environment')
        {
          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_options' => '',
            'command_src' => "\\" . $this->command_name . "{" . $env . "}",
            'line_number' => $this->line_number,
          ]));

        }
        else if (in_array($env, [...self::ENVS_POST_OPTIONS, ...self::$thm_env]))
        {
          try {
            $options = $this->getContentBetweenDelimiters('[', ']');
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
          }

          $command_src = "\\" . $this->command_name . "{" . $env . "}";

          if ($options !== '') $command_src .= "[" . $options . "]";

          $type = $this->getCommandType($this->command_name, $env);

          $this->addToken(new Token([
            'type' => $type,
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_options' => $options,
            'command_src' => $command_src,
            'line_number' => $this->line_number,
          ]));

        }
        else if ($this->getCommandType($this->command_name, $env) === 'tabular-environment')
        {

          try {
            $options = $this->getContentBetweenDelimiters('[', ']');
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
          }

          $command_src = "\\" . $this->command_name . "{" . $env . "}";

          if ($options !== '') {
            $command_src .= "[" . $options . "]";
          }

          $this->forward();

          try {
            // $args = $this->getContentBetweenDelimiters('{', '}');
            $args = $this->getCommandContent();
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
          }

          if ($args === '') {
            throw new \Exception("Parse error: Missing arguments for tabular environment on line " . $this->line_number);
          }

          $command_src .= "{" . $args . "}";

          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_args' => [$args],
            'command_options' => $options,
            'command_src' => $command_src,
            'line_number' => $this->line_number,
          ]));
        }
        else if ($this->getCommandType($this->command_name, $env) === 'bibliography-environment')
        {
          $command_src = "\\" . $this->command_name . "{" . $env . "}";

          try {
            $args = $this->getContentBetweenDelimiters('{', '}');
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
          }

          if ($args === '') {
            throw new \Exception("Parse error: Missing arguments for tabular environment on line " . $this->line_number);
          }

          $command_src .= "{" . $args . "}";

          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_args' => [$args],
            'command_src' => $command_src,
            'line_number' => $this->line_number,
          ]));
        }
        else
        {
          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_src' => "\\begin{" . $env . "}",
            'line_number' => $this->line_number,
          ]));
        }
      }
      else if ($this->getCommandType($this->command_name) === 'font-cmd')
      {

        // backup because after running consumeUntilNonAlpha()
        // the cursor is at the first non-alpha character
        $this->backup();

        $this->addToken(new Token([
          'type' => 'font-cmd',
          'command_name' => $this->command_name,
          'command_content' => '',
          'command_options' => '',
          'command_src' => "\\" . $this->command_name,
          'body' => '',
          'line_number' => $this->line_number,
        ]));
      }
      else if ($this->getCommandType($this->command_name) === 'one-arg-cmd')
      {
        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $label = '';

        if (in_array($this->command_name, ['ref', 'eqref', 'label']))
          $label = self::$ref_labels[$content] ?? '?';

        $this->addToken(new Token([
          'type' => $this->command_name,
          'command_name' => $this->command_name,
          'command_content' => $content,
          'command_options' => $label,
          'command_src' => "\\" . $this->command_name . "{" . $content . "}",
          'body' => $content,
          'line_number' => $this->line_number,
        ]));
      }
      else if ($this->getCommandType($this->command_name) === 'one-arg-cmd-pre-options')
      {
        try {
          $token = $this->tokenizeCmdWithOptionsArg();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        if ($this->command_name === 'cite') $this->tokenizeCitation($token);

        $this->addToken($token);
      }
      else if ($this->getCommandType($this->command_name) === 'cmd-with-options')
      {
        try {
          $token = $this->tokenizeCmdWithOptions();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $this->addToken($token);
      }
      else if ($this->getCommandType($this->command_name) === 'font-declaration')
      {
        $this->addFontDeclarationToken();
      }
      else
      {
        $this->buffer .= "\\" . $this->command_name;
        $this->backup();
      }
    }

    $this->addBufferAsToken();

    $this->postProcessTokens();

    return $this->tokens;
  }

  private function preprocessLatexSource(string $latex_src)
  {

    // Remove any spaces at the right end of lines and reconstruct the source
    $latex_src = implode("\n", array_map(trim(...), explode("\n", $latex_src)));

    $n = StrHelper::findStringLineNumber("begin{document}", $latex_src);

    $this->line_number = $n > -1 ? $n : 1;

    $html_src = preg_replace('/.*\\\begin\s*{document}(.*)\\\end\s*{document}.*/sm', "$1", $latex_src);

    // $html_src = StrHelper::DeleteLatexComments($html_src);

    // Replace $$...$$ with \begin{equation*}...\end{equation*}
    // note space after \begin{equation*}
    $html_src = preg_replace('/\$\$(.*?)\$\$/s', '\\begin{equation*} $1 \\end{equation*}', $html_src);

    // Then replace $...$ with \begin{math}...\end{math}
    // note space after \begin{math}
    // $html_src = preg_replace('/(?<!\\\)\$(.*?)\$/s', "\\begin{math} $1\\end{math}", $html_src);
    $html_src = preg_replace('/(?<!\\\)\$(.*?)\$/s', "\\( $1 \\)", $html_src);

    // Replace \[...\] with \begin{equation*}...\end{equation*}
    // note space after \begin{equation*}
    $html_src = preg_replace('/([^\\\])(?:\\\)(?:\[)/', '$1\\begin{equation*} ', $html_src);
    $html_src = preg_replace('/^\s*(?:\\\)(?:\[)/m', '$1\\begin{equation*} ', $html_src);
    $html_src = str_replace('\]', '\end{equation*}', $html_src);

    return $html_src;
  }

  private function addToken(Token $token)
  {

    $this->addBufferAsToken();

    $this->tokens[] = $token;
  }

  private function addSymbolToken(string $char)
  {

    $this->getNextChar();

    try {
      $token = $this->tokenizeCmdWithOptions();
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    $token->type = 'symbol';
    $token->body = $char;

    $this->addToken($token);
  }

  private function addFontDeclarationToken()
  {
    $this->addBufferAsToken();

    $this->tokens[] = new Token([
      'type' => 'font-declaration',
      'body' => self::FONT_DECLARATIONS[$this->command_name],
      'line_number' => $this->line_number,
    ]);
  }

  private function addBufferAsToken()
  {

    if ($this->buffer === '') return;

    // Replace more than two newlines with two newlines
    // $text = preg_replace('/\n{3,}/', "\n\n", $this->buffer);
    $text = $this->buffer;

    $this->tokens[] = new Token([
      'type' => 'text',
      'body' => $text,
      'line_number' => $this->line_number,
    ]);

    $this->buffer = '';
  }

  private function addInlineMathToken($char)
  {
    // Treat inline math like a begin/end environment
    $this->addToken(new Token([
      'type' => 'inlinemath',
      'command_name' => $char === '(' ? 'begin' : 'end',
      'command_content' => 'inlinemath',
      'command_options' => '',
      'command_src' => "\\" . $char,
      'line_number' => $this->line_number,
    ]));
  }

  private function addGroupEnvToken($char)
  {
    $this->addBufferAsToken();

    $command_name = $char === '{' ? 'begin' : 'end';

    $last_token = $this->getLastToken();
    $options = $last_token->command_name;
    $src = $last_token->command_src;

    $command_content = 'unnamed';

    $this->tokens[] = new Token([
      'type' => 'group-environment',
      'command_name' => $command_name,
      'command_content' => $command_content,
      'command_src' => $src,
      'command_options' => $options,
      'line_number' => $this->line_number,
    ]);
  }

  private function addUnknownToken($command_name)
  {
    // These tokens will be ignored unless inside
    // a verbatim environment or math environment
    $this->addToken(new Token([
      'type' => 'unknown',
      'command_name' => $command_name,
      'command_src' => "\\" . $command_name,
      'line_number' => $this->line_number,
    ]));
  }

  private function backup()
  {
    if ($this->getChar() === "\n") $this->line_number--;
    $this->cursor--;
  }

  private function forward()
  {
    if ($this->getChar() === "\n") $this->line_number++;
    $this->cursor++;
  }

  private function getChar()
  {
    return $this->cursor < $this->num_chars ? $this->stream[$this->cursor] : null;
  }

  private function getNextChar()
  {
    $this->prev_char = $this->getChar();
    $this->cursor++;
    $char = $this->getChar();

    if ($char === "\n") $this->line_number++;

    return $char;
  }

  private function getCommandType($name, $env = null)
  {

    if ($name == 'begin' || $name == 'end') {

      if (in_array($env, self::DISPLAY_MATH_ENVIRONMENTS)) return 'displaymath-environment';

      if (in_array($env, self::LIST_ENVIRONMENTS))     return 'list-environment';

      if (in_array($env, self::TABULAR_ENVIRONMENTS))  return 'tabular-environment';

      if (in_array($env, self::$thm_env)) return 'thm-environment';

      if ($env === 'verbatim') return 'verbatim-environment';

      if ($env === 'thebibliography') return 'bibliography-environment';

      return 'environment';
    }

    if (in_array($name, self::SECTION_COMMANDS)) return 'section-cmd';

    if (in_array($name, self::FONT_COMMANDS))    return 'font-cmd';

    if (in_array($name, array_keys(self::FONT_DECLARATIONS))) return 'font-declaration';

    if (in_array($name, self::ONE_ARGS_CMDS)) return 'one-arg-cmd';

    if (in_array($name, self::ONE_ARGS_CMDS_PRE_OPTIONS)) return 'one-arg-cmd-pre-options';

    if (in_array($name, self::CMD_WITH_OPTIONS)) return 'cmd-with-options';

    return 'text';
  }

  private function tokenizeSection(): Token
  {

    $content = '';
    $options = '';
    $src = '\\' . $this->command_name;
    $STARRED = false;
    $TOC_ENTRY = false;

    $ALLOWED_CHARS = [' ', '*', '{', '['];

    while (!is_null($char = $this->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid sectioning command");
      }

      if ($char === ' ') {
        $this->cursor++;
        continue;
      }

      if ($char === '*') {

        if ($STARRED || $TOC_ENTRY) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        $this->cursor++;
        $this->command_name .= '*';
        $src .= '*';
        $STARRED = true;
        continue;
      }

      if ($char === '[') {

        if ($TOC_ENTRY) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        try {
          $options = $this->getContentUpToDelimiter(']');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        $this->cursor++;
        $TOC_ENTRY = true;
        continue;

      }

      if ($char === '{' ) {

        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '{' . $content . '}';
        break;

      }
    }

    return new Token([
      'type' => 'section-cmd',
      'command_name' => $this->command_name,
      'command_content' => $content,
      'command_options' => $options,
      'command_src' => $src,
      'line_number' => $this->line_number,
    ]);
  }

  private function tokenizeCmdWithOptionsArg(string|null $type = null): Token
  {
    $content = '';
    $options = '';
    $src = '\\' . $this->command_name;
    $OPTIONS = false;

    $ALLOWED_CHARS = [' ', '{', '['];

    while (!is_null($char = $this->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
      }

      if ($char === ' ') {
        $this->cursor++;
        continue;
      }

      if ($char === '[') {

        if ($OPTIONS) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        try {
          $options = $this->getContentUpToDelimiter(']');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        $this->cursor++;
        $OPTIONS = true;
        continue;
      }

      if ($char === '{') {

        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '{' . $content . '}';

        break;
      }
    }

    return new Token([
      'type' => $this->command_name,
      'command_name' => $this->command_name,
      'command_content' => $content,
      'command_options' => $options,
      'command_src' => $src,
      'body' => $content,
      'line_number' => $this->line_number,
    ]);
  }

  private function tokenizeCmdWithArgOptions(string|null $type = null): Token
  {
    $content = '';
    $options = '';
    $src = '\\' . $this->command_name;
    $ARGS_DONE = false;

    $ALLOWED_CHARS = [' ', '{', '['];

    while (!is_null($char = $this->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        if (!$ARGS_DONE) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }
        $this->backup();
        break;
      }

      if ($char === ' ') {
        $this->cursor++;
        continue;
      }

      if ($char === '[') {

        if (!$ARGS_DONE) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        try {
          $options = $this->getContentUpToDelimiter(']');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        break;
      }

      if ($char === '{') {

        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '{' . $content . '}';

        $ARGS_DONE = true;
      }
    }

    return new Token([
      'type' => $type ?? $this->command_name,
      'command_name' => $this->command_name,
      'command_content' => $content,
      'command_options' => $options,
      'command_src' => $src,
      'line_number' => $this->line_number,
    ]);
  }

  private function tokenizeCmdWithOptions(string|null $type = null): Token
  {
    $options = '';
    $src = '\\' . $this->command_name;

    $ALLOWED_CHARS = [' ', '['];

    while (!is_null($char = $this->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $this->backup();
        break;
      }

      if ($char === ' ') {
        $this->cursor++;
        continue;
      }

      if ($char === '[') {

        try {
          $options = $this->getContentUpToDelimiter(']');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        break;
      }
    }

    return new Token([
      'type' => $this->command_name,
      'command_name' => $this->command_name,
      'command_content' => '',
      'command_options' => $options,
      'command_src' => $src,
      'line_number' => $this->line_number,
    ]);
  }

  private function tokenizeCitation($token): void
  {
    $labels = array_map(trim(...), explode(',', $token->command_content));

    $citation_numbers = [];

    foreach ($labels as $label) {
      if (isset(self::$citations[$label])) {
        $citation_numbers[] = self::$citations[$label];
      } else {
        $citation_numbers[] = '?';
      }
    }

    $token->body = implode(',', $citation_numbers);
  }

  private function postProcessTokens(): void
  {

    foreach ($this->tokens as $k => $token) {

      if ($token->type !== 'text') continue;

      if ($k === count($this->tokens) - 1) continue;

      if ($k === 0) {
        $this->tokens[$k]->body = rtrim($token->body);
        continue;
      }

      if (str_contains($this->tokens[$k + 1]->type, 'environment') || $this->tokens[$k + 1]->type == 'section-cmd') {
        $this->tokens[$k]->body = rtrim($token->body);
      }

      if (str_contains($this->tokens[$k - 1]->type, 'environment') || $this->tokens[$k - 1]->type == 'section-cmd') {
        $this->tokens[$k]->body = ltrim($token->body);
      }
    }
  }

  private function getCommandContent()
  {

    try {
      $this->consumeSpaceUntilTarget('{');
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    $content = '';

    $brace_count = 1;

    $char = $this->getNextChar();

    while (!is_null($char) && $brace_count > 0) {

      if ($char === "\n" && $this->prev_char === "\n") {
        $so_far = '\\' . $this->command_name . '{' . $content;
        throw new \Exception("$so_far __ <--- Parse error on line {$this->line_number}: invalid syntax");
      }

      if ($char !== '}') {

        $content .= $char;

        if ($char === '{') $brace_count++;

        $char = $this->getNextChar();
      } else {

        $brace_count--;

        if ($brace_count > 0) {
          $content .= '}';
          $char = $this->getNextChar();
        }
      }
    }

    if ($this->cursor === $this->num_chars) {
      $so_far = '\\' . $this->command_name . '{' . $content;
      throw new \Exception("$so_far __ <--- Missing closing brace } on line {$this->line_number}");
    }

    return $content;
  }

  public function prettyPrintTokens()
  {
    $output = '';

    foreach ($this->tokens as $token) {
      $output .= (string) $token . "<br>";
    }

    return $output;
  }

  /**
   * Get the content between two delimiters
   * Nesting not allowed.
   * 
   * The cursor should be just before $left delimiter
   * White space is ignored and is the only character allowed
   * before the $left delimiter, otherwise we break out of the loop
   */
  private function getContentBetweenDelimiters($left_delim, $right_delim)
  {
    $content = '';
    $ALLOWED_CHARS = [' ', $left_delim];

    while (!is_null($char = $this->getNextChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $this->backup();
        break;
      }

      if ($char === ' ') {
        $this->cursor++;
        continue;
      }

      if ($char === $left_delim) {

        try {
          $content = $this->getContentUpToDelimiter($right_delim);
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        break;
      }
    }

    return $content;
  }

  /**
   * Get the content up to the next delimiter
   * 
   * 
   * The cursor should be just before the delimiter
   * The only character not allowed before the 
   * delimiter is \n
   */
  private function getContentUpToDelimiter($delimiter)
  {
    $content = '';

    $char = $this->getNextChar();

    while (!is_null($char) && $char !== $delimiter) {

      if ($char === "\n") {
        throw new \Exception("$content <--- Parse error on line {$this->line_number}: newline invalid syntax");
      }

      $content .= $char;
      $char = $this->getNextChar();
    }

    if ($this->cursor === $this->num_chars) {
      throw new \Exception("$content <--- Parse error on line {$this->line_number}: missing $delimiter");
    }

    return $content;
  }

  private function getEnvName()
  {

    try {
      $this->consumeSpaceUntilTarget('{');
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    $env = '';

    $char = $this->getNextChar();

    while (!is_null($char) && $char !== '}') {

      if (!(ctype_alpha($char) || $char === '*')) {
        throw new \Exception($env . $char . " <--- Invalid environment name at line {$this->line_number}");
      }

      $env .= $char;
      $char = $this->getNextChar();
    }

    if ($this->cursor === $this->num_chars) {
      throw new \Exception("Expected } at line {$this->line_number}");
    }

    return $env;
  }

  private function consumeSpaceUntilTarget($target)
  {

    if ($this->cursor === $this->num_chars) {
      throw new \Exception("Unexpected end of file on line {$this->line_number}");
    }

    $char = $this->getChar();

    while (!is_null($char) && $char === ' ') {
      $char = $this->getNextChar();
    }

    if ($char === $target) {
      return;
    }

    throw new \Exception("Parse error: missing $target on line {$this->line_number}");
  }

  private function consumeUntilNonAlpha($from_cursor = true)
  {

    $char = $from_cursor ? $this->getChar() : $this->getNextChar();

    $alpha_text = '';

    while (!is_null($char) && ctype_alpha($char)) {
      $alpha_text .= $char;
      $char = $this->getNextChar();
    }

    return $alpha_text;
  }

  private function consumeUntilTarget($target)
  {

    if ($this->cursor === $this->num_chars) {
      throw new \Exception("Unexpected end of file on line {$this->line_number}");
    }

    $char = $this->getChar();

    while (!is_null($char) && $char !== $target) {
      $char = $this->getNextChar();
    }

    if ($char === $target) {
      return;
    }

    throw new \Exception("Parse error: missing $target on line {$this->line_number}");
  }

  private function getLastToken()
  {
    $count = count($this->tokens);

    return $count > 0 ? $this->tokens[$count - 1] : null;
  }
}
