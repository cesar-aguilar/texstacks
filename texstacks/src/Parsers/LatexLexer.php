<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Token;
use TexStacks\Helpers\StrHelper;
use TexStacks\Parsers\Tokenizer;

class LatexLexer extends Tokenizer
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
    'displaymath',
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
    'title',
    'author',
    'contrib',
    'subjclass',
  ];

  const ONE_ARGS_CMDS = [
    'label',
    'ref',
    'eqref',
    'tag',
    'date',
    'address',
    'curraddr',
    'email',
    'urladdr',
    'dedicatory',
    'thanks',
    'translator',
    'keywords',
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
    'text',
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
    'em' => 'italic',
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
    'description',
    'algorithmic',
  ];

  const ALPHA_SYMBOLS = [
    'S',
    'P',
    'pounds',
    'copyright',
    'dag',
    'ddag',
  ];

  const SPACING_CMDS = [
    'quad',
    'qquad',
    'hspace',
    'vspace',
    'smallskip',
    'medskip',
    'bigskip',
    'noindent',
  ];

  const TWO_ARGS_CMDS = [
    'texorpdfstring',
  ];

  const ACTION_CMDS = [
    'appendix',
  ];
   
  private static array $thm_env = [];
 
  public function __construct($data = [])
  {
    if (isset($data['thm_env'])) self::setTheoremEnvs($data['thm_env']);

    $this->line_number = $data['line_number_offset'] ?? 1;
  }
 
  public static function setTheoremEnvs($thm_envs)
  {
    self::$thm_env = array_unique([...self::$thm_env, ...$thm_envs]);
  }

  public function tokenize(string $latex_src)
  {

    $this->init();

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

      if ($char === "$") {

        if ($this->getNextChar() === "$") {
          try {
            $this->in_math = !$this->in_math;
            $this->addDisplayMathToken($this->in_math ? '[' : ']');
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
          }
        } else {
          $this->backup();
          try {
            $this->in_math = !$this->in_math;
            $this->addInlineMathToken($this->in_math ? '(' : ')');
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
          }
        }

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

        if ($char === '[' || $char === ']') {
          $this->addDisplayMathToken($char);
          continue;
        }

        if (key_exists($char, self::ACCENT_CMDS)) {
          $this->addAccentToken($char);
          continue;
        }

        $this->command_name = "\\";

        $this->addSymbolToken($char);
        continue;
      }

      // The current char is alphabetic so consume and
      // return the command name; cursor will be a non-alphabetic char
      // when complete
      $this->command_name = $this->consumeUntilNonAlpha();

      // Make token
      if ($this->getCommandType($this->command_name) === 'section-cmd') {
        try {
          $token = $this->tokenizeSection();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage() . " at line {$this->line_number}");
        }

        $this->addToken($token);
      } else if ($this->getCommandType($this->command_name) === 'environment') {

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
        } else if ($this->getCommandType($this->command_name, $env) === 'displaymath-environment') {
          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_options' => '',
            'command_src' => "\\" . $this->command_name . "{" . $env . "}",
            'line_number' => $this->line_number,
          ]));
        } else if (in_array($env, [...self::ENVS_POST_OPTIONS, ...self::$thm_env, ...self::LIST_ENVIRONMENTS])) {
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
        } else if ($this->getCommandType($this->command_name, $env) === 'tabular-environment') {

          try {
            $options = $this->getContentBetweenDelimiters('[', ']');
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
          }

          $command_src = "\\" . $this->command_name . "{" . $env . "}";

          if ($options !== '') {
            $command_src .= "[" . $options . "]";
          }

          try {
            $args = $this->getCommandContent(move_forward: true);
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
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
        } else if ($this->getCommandType($this->command_name, $env) === 'bibliography-environment') {

          $command_src = "\\" . $this->command_name . "{" . $env . "}";

          try {
            $args = $this->getCommandContent(move_forward: true);
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
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
        } else {
          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_src' => "\\begin{" . $env . "}",
            'line_number' => $this->line_number,
          ]));
        }
      } else if ($this->getCommandType($this->command_name) === 'font-cmd') {

        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $this->addFontCommandToken($content);

      } else if ($this->getCommandType($this->command_name) === 'one-arg-cmd') {
        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $label = '';

        if (in_array($this->command_name, ['ref', 'eqref', 'label']))
          $label = self::$ref_labels[$content] ?? '?';

        // $type = in_array($this->command_name, ['date', 'address', 'curraddr', 'email', 'urladdr', 'dedicatory', 'thanks', 'translator', 'keywords']) ? 'ignore' : $this->command_name;
        $type = $this->command_name;

        $this->addToken(new Token([
          'type' => $type,
          'command_name' => $this->command_name,
          'command_content' => $content,
          'command_options' => $label,
          'command_src' => "\\" . $this->command_name . "{" . $content . "}",
          'body' => $content,
          'line_number' => $this->line_number,
        ]));
      } else if ($this->getCommandType($this->command_name) === 'one-arg-cmd-pre-options') {
        try {
          $token = $this->tokenizeCmdWithOptionsArg();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $this->addToken($token);
      } else if ($this->getCommandType($this->command_name) === 'cmd-with-options') {
        try {
          $token = $this->tokenizeCmdWithOptions();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $this->addToken($token);
      } else if ($this->getCommandType($this->command_name) === 'font-declaration') {
        $this->addFontDeclarationToken();
      } else if ($this->getCommandType($this->command_name) === 'alpha-symbol') {
        $this->addToken(new Token([
          'type' => 'alpha-symbol',
          'command_name' => $this->command_name,
          'command_src' => "\\" . $this->command_name,
          'body' => $this->command_name,
          'line_number' => $this->line_number,
        ]));
        $this->backup();
      } else if ($this->getCommandType($this->command_name) === 'spacing-cmd') {

        if (!str_contains($this->command_name, 'space')) {

          $this->addToken(new Token([
            'type' => 'spacing-cmd',
            'command_name' => $this->command_name,
            'command_src' => "\\" . $this->command_name,
            'line_number' => $this->line_number,
          ]));

          if ($this->getChar() !== ' ') $this->backup();

          continue;
        }

        $this->consumeWhiteSpace();

        if ($this->getChar() === '*') {
          $this->command_name .= '*';
          $this->forward();
        }

        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $this->addToken(new Token([
          'type' => 'spacing-cmd',
          'command_name' => $this->command_name,
          'command_content' => $content,
          'command_src' => "\\" . $this->command_name . "{" . $content . "}",
          'line_number' => $this->line_number,
        ]));
      } else if ($this->getCommandType($this->command_name) === 'two-args-cmd') {

        try {
          $arg_1 = $this->getCommandContent();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
        }

        try {
          $arg_2 = $this->getCommandContent(move_forward: true);
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
        }

        $this->addToken(new Token([
          'type' => 'two-args-cmd',
          'command_name' => $this->command_name,
          'command_args' => ['arg1' => $arg_1, 'arg2' => $arg_2],
          'command_src' => "\\" . $this->command_name . "{" . $arg_1 . "}{" . $arg_2 . "}",
          'line_number' => $this->line_number,
        ]));
      } else if($this->getCommandType($this->command_name) === 'action-cmd') {
        $this->addToken(new Token([
          'type' => 'action-cmd',
          'command_name' => $this->command_name,
          'command_src' => "\\" . $this->command_name,
          'line_number' => $this->line_number,
        ]));
      } else {
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
    // $latex_src = implode("\n", array_map(trim(...), explode("\n", $latex_src)));

    $n = StrHelper::findStringLineNumber("begin{document}", $latex_src);

    $this->line_number = $n > -1 ? $n : $this->line_number;

    $html_src = preg_replace('/.*\\\begin\s*{document}(.*)\\\end\s*{document}.*/sm', "$1", $latex_src);

    // $html_src = StrHelper::DeleteLatexComments($html_src);

    // Replace $$...$$ with \begin{equation*}...\end{equation*}
    // note space after \begin{equation*}
    // $html_src = preg_replace('/\$\$(.*?)\$\$/s', '\\begin{equation*} $1 \\end{equation*}', $html_src);

    // Then replace $...$ with \begin{math}...\end{math}
    // note space after \begin{math}
    // $html_src = preg_replace('/(?<!\\\)\$(.*?)\$/s', "\\begin{math} $1\\end{math}", $html_src);
    // $html_src = preg_replace('/(?<!\\\)\$(.*?)\$/s', "\\( $1 \\)", $html_src);

    // Replace \[...\] with \begin{equation*}...\end{equation*}
    // note space after \begin{equation*}
    // $html_src = preg_replace('/([^\\\])(?:\\\)(?:\[)/', '$1\\begin{equation*} ', $html_src);
    // $html_src = preg_replace('/^\s*(?:\\\)(?:\[)/m', '$1\\begin{equation*} ', $html_src);
    // $html_src = str_replace('\]', '\end{equation*}', $html_src);

    return $html_src;
  }

  private function addFontCommandToken($content)
  {
    $this->addToken(new Token([
      'type' => 'font-cmd',
      'command_name' => $this->command_name,
      'command_content' => $content,
      'command_options' => '',
      'command_src' => "\\" . $this->command_name . "{" . $content . "}",
      'body' => $content,
      'line_number' => $this->line_number,
    ]));
  }

  private function addFontDeclarationToken()
  {
    $this->addToken(new Token([
      'type' => 'font-declaration',
      'body' => self::FONT_DECLARATIONS[$this->command_name],
      'line_number' => $this->line_number,
    ]));

    if ($this->getChar() !== ' ') $this->backup();
  }

  private function getCommandType($name, $env = null)
  {

    if ($name == 'begin' || $name == 'end') {

      if (in_array($env, self::DISPLAY_MATH_ENVIRONMENTS)) return 'displaymath-environment';

      if (in_array($env, self::LIST_ENVIRONMENTS))     return 'environment:list';

      if (in_array($env, self::TABULAR_ENVIRONMENTS))  return 'tabular-environment';

      if (in_array($env, self::$thm_env)) return 'thm-environment';

      if ($env === 'math') return 'inlinemath';

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

    if (in_array($name, self::ALPHA_SYMBOLS)) return 'alpha-symbol';

    if (in_array($name, self::SPACING_CMDS)) return 'spacing-cmd';

    if (in_array($name, self::TWO_ARGS_CMDS)) return 'two-args-cmd';

    if (in_array($name, self::ACTION_CMDS)) return 'action-cmd';

    return 'text';
  }

  private function postProcessTokens(): void
  {

    foreach ($this->tokens as $k => $token) {

      if ($token->type !== 'text') continue;

      if ($k === count($this->tokens) - 1) continue;

      if ($k === 0) {
        $this->tokens[$k]->body = rtrim($token->body, "\n");
        continue;
      }

      if (str_contains($this->tokens[$k + 1]->type, 'environment') || $this->tokens[$k + 1]->type == 'section-cmd') {
        $this->tokens[$k]->body = rtrim($token->body);
        // $this->tokens[$k]->body = preg_replace('/(\n[\s\t]*){2,}/', '', $token->body);
      }

      if (str_contains($this->tokens[$k - 1]->type, 'environment') || $this->tokens[$k - 1]->type == 'section-cmd') {
        $this->tokens[$k]->body = ltrim($token->body);
        // $this->tokens[$k]->body = preg_replace('/(\n[\s\t]*){2,}/', '', $token->body);
      }
    }
  }

}
