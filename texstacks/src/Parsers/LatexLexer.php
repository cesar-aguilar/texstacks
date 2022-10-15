<?php

namespace TexStacks\Parsers;

use TexStacks\Helpers\StrHelper;
use TexStacks\Parsers\Token;

class LatexLexer
{

  const AMS_THEOREM_ENVIRONMENTS = [
    'theorem',
    'proposition',
    'lemma',
    'corollary',
    'definition',
    'conjecture',
  ];

  const AMS_MATH_ENVIRONMENTS = [
    'math',
    'align', 'align*',
    'aligned',
    'alignedat', 'alignedat*',
    'alignat', 'alignat*',
    'flalign', 'flalign*',
    'subarray',
    'cases',
    'CD',
    'gather', 'gather*',
    'gathered', 'gathered*',
    'equation', 'equation*',
    'eqnarray', 'eqnarray*',
    'multline', 'multline*',
    'split',
    'matrix',
    'pmatrix',
    'smallmatrix',
    'Bmatrix', 'bmatrix',
    'Vmatrix', 'vmatrix',
  ];

  const ENVS_POST_OPTIONS = [
    'itemize',
    'enumerate',
    'compactenum',
    'compactitem',
    'asparaenum',
    'figure',
    'table',
    'center',
    'verbatim',
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

  const CMDS_POST_OPTIONS = [
    'item',
  ];

  const ONE_ARGS_CMDS_PRE_OPTIONS = [
    'includegraphics',
    'caption',
  ];

  const ONE_ARGS_CMDS = [
    'label',
    'ref',
    'eqref',    
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
  ];

  const LIST_ENVIRONMENTS = [
    'itemize',
    'enumerate',    
    'compactenum',
    'compactitem',
    'asparaenum',
  ];

  private array $tokens = [];
  private string $buffer = '';
  private int $line_number = 0;
  private string $stream;
  private int $cursor;
  private string $prev_char;
  private int $num_chars = 0;
  private string $command_name;

  public function tokenize(string $latex_src)
  {

    $this->stream = $this->normalizeLatexSource($latex_src);

    $this->num_chars = strlen($this->stream);

    if ($this->num_chars === 0) return [];
  
    $this->cursor = -1;
    
    while (!is_null($char = $this->getNextChar()))
    {

      if ($char !== '\\') {
        $this->buffer .= $char;
        continue;
      }

      $char = $this->getNextChar();

      // If char is non-alphabetic then we have a control symbol
      if (!ctype_alpha($char ?? '')) {
        $this->buffer .= "\\" . $char;
        continue;
      }
      
      $this->command_name = $this->consumeUntilNonAlpha();

      // Make token
      if (in_array($this->command_name, self::SECTION_COMMANDS))
      {
        try {
          $token = $this->tokenizeSection();
        } catch (\Exception $e) {
          die($e->getMessage());
        }
        
        $this->addToken($token);
        
      }
      else if ($this->command_name === 'begin' || $this->command_name === 'end')
      {

        try {
          $env = $this->getEnvName();
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        if ($this->command_name === 'end')
        {
          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_src' => "\\end{" . $env. "}",
            'line_number' => $this->line_number,
          ]));
        }
        else if (in_array($env, self::DISPLAY_MATH_ENVIRONMENTS))
        {

          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_options' => '',
            'command_src' => "\\" . $this->command_name . "{" . $env. "}",
            'line_number' => $this->line_number,
          ]));

        }
        else if (in_array($env, [...self::ENVS_POST_OPTIONS, ...self::AMS_THEOREM_ENVIRONMENTS]))
        {
          try {
            $options = $this->getContentBetweenDelimiters('[', ']');
          } catch (\Exception $e) {
            die($e->getMessage());
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
        else if (in_array($env, self::TABULAR_ENVIRONMENTS))
        {
          try {
            $options = $this->getContentBetweenDelimiters('[', ']');
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
          }

          $command_src = "\\" . $this->command_name . "{" . $env . "}";

          if ($options !== '') $command_src .= "[" . $options . "]";

          try {
            $args = $this->getContentBetweenDelimiters('{', '}');
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
          }

          if ($args === '') {
            throw new \Exception("Missing arguments for tabular environment on line " . $this->line_number);
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
        else
        {
          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_src' => "\\begin{" . $env. "}",
            'line_number' => $this->line_number,
          ]));
        }

      }
      else if (in_array($this->command_name, self::FONT_COMMANDS))
      {

        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $this->addToken(new Token([
          'type' => 'font-environment',
          'command_name' => $this->command_name,
          'command_content' => $content,
          'command_options' => '',
          'command_src' => "\\" . $this->command_name . "{" . $content. "}",
          'body' => $content,
          'line_number' => $this->line_number,
        ]));

      }
      else if (in_array($this->command_name, self::ONE_ARGS_CMDS))
      {
        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $this->addToken(new Token([
          'type' => $this->getCommandType($this->command_name),
          'command_name' => $this->command_name,
          'command_content' => $content,
          'command_options' => '',
          'command_src' => "\\" . $this->command_name . "{" . $content. "}",
          'body' => $content,
          'line_number' => $this->line_number,
        ]));
      }
      else if (in_array($this->command_name, self::ONE_ARGS_CMDS_PRE_OPTIONS))
      {
        try {
          $token = $this->tokenizeCmdWithOptionsArg();
        } catch (\Exception $e) {
          die($e->getMessage());
        }
  
        $this->addToken($token);
      }
      else if (in_array($this->command_name, self::CMDS_POST_OPTIONS))
      {
        try {
          $token = $this->tokenizeCmdWithOptions();
        } catch (\Exception $e) {
          die($e->getMessage());
        }
        
        $this->addToken($token);
      }
      else
      {
        $this->buffer .= "\\" . $this->command_name . $this->getChar();
      }

    }

    $this->addBufferAsToken();

    return $this->tokens;
      
  }

  private function normalizeLatexSource(string $latex_src)
  {

    $n = StrHelper::findStringLineNumber("begin{document}", $latex_src);

    $this->line_number = $n > -1 ? $n : 1;

    $html_src = preg_replace('/.*\\\begin\s*{document}(.*)\\\end\s*{document}.*/sm', "$1", $latex_src);

    $html_src = StrHelper::DeleteLatexComments($html_src);

    $html_src = str_replace('``', '"', $html_src);

    // Replace less than and greater than symbols with latex commands
    // note space after/before \lt and \gt
    $html_src = str_replace('<', '&lt;', $html_src);
    $html_src = str_replace('>', '&gt;', $html_src);

    // Replace dollar sign with html entity
    $html_src = str_replace('\\$', '&#36;', $html_src);

    // Replace $$...$$ with \begin{equation*}...\end{equation*}
    // note space after \begin{equation*}
    $html_src = preg_replace('/\$\$(.*?)\$\$/s', '\\begin{equation*} $1\\end{equation*}', $html_src);

    // Then replace $...$ with \begin{math}...\end{math}
    // note space after \begin{math}
    // $html_src = preg_replace('/\$(.*?)\$/s', "\\begin{math} $1\\end{math}", $html_src);
    $html_src = preg_replace('/\$(.*?)\$/s', "\\($1\\)", $html_src);

    // Replace \[...\] with \begin{equation*}...\end{equation*}
    // note space after \begin{equation*}
    $html_src = preg_replace('/([^\\\])(?:\\\)(?:\[)/', '$1\\begin{equation*} ', $html_src);
    $html_src = preg_replace('/^\s*(?:\\\)(?:\[)/m', '$1\\begin{equation*} ', $html_src);
    $html_src = str_replace('\]', '\end{equation*}', $html_src);

    // Replace more than two newlines with two newlines
    // $html_src = preg_replace('/\n{3,}/', "\n\n", $html_src);
    // dd(StrHelper::addLineNumbers($html_src, $n));
    return $html_src;

  }

  private function addToken(Token $token) {

      $this->addBufferAsToken();

      $this->tokens[] = $token;

  }

  private function addBufferAsToken()
  {

      if ($this->buffer === '') return;

      $this->tokens[] = new Token([
          'type' => 'text',        
          'body' => $this->buffer,
          'line_number' => $this->line_number,
      ]);

      $this->buffer = '';
  
  }

  private function backup()
  {
    if ($this->getChar() === "\n") $this->line_number--;
    $this->cursor--;
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

  private function getCommandType($name, $content=null)
  {

    if ($name == 'begin' || $name == 'end') {

      if (in_array($content, self::DISPLAY_MATH_ENVIRONMENTS)) return 'math-environment';

      if (in_array($content, self::LIST_ENVIRONMENTS))     return 'list-environment';

      if (in_array($content, self::TABULAR_ENVIRONMENTS))  return 'tabular-environment';

      if (in_array($content, self::FONT_COMMANDS))         return 'font-environment';

      return 'environment';

    }

    if (in_array($name, self::SECTION_COMMANDS)) return 'section-cmd';

    return match ($name) {

      'includegraphics' => 'includegraphics',

      'label' => 'label',

      'caption' => 'caption',

      'item' => 'item',

      'ref' => 'ref',

      'eqref' => 'eqref',

      default => 'text',

    };

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
          die($e->getMessage());
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
          die($e->getMessage());
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

  private function tokenizeCmdWithOptionsArg(string|null $type=null) : Token
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
          die($e->getMessage());
        }

        $src .= '[' . $options . ']';

        $this->cursor++;
        $OPTIONS = true;
        continue;
      }

      if ($char === '{' ) {
 
        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $src .= '{' . $content . '}';
        
        break;
  
      }

    }

    return new Token([
      'type' => $this->getCommandType($this->command_name),
      'command_name' => $this->command_name,
      'command_content' => $content,
      'command_options' => $options,
      'command_src' => $src,
      'body' => $content,
      'line_number' => $this->line_number,
    ]);
  
  }

  private function tokenizeCmdWithArgOptions(string|null $type=null) : Token
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
          die($e->getMessage());
        }

        $src .= '[' . $options . ']';

        break;
      }

      if ($char === '{' ) {
 
        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          die($e->getMessage());
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

  private function tokenizeCmdwithOptions(string|null $type=null) : Token
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
          die($e->getMessage());
        }

        $src .= '[' . $options . ']';

        break;
      }

    }

    return new Token([
      'type' => $this->getCommandType($this->command_name),
      'command_name' => $this->command_name,
      'command_content' => '',
      'command_options' => $options,
      'command_src' => $src,
      'line_number' => $this->line_number,
    ]);
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

      }
      else
      {

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
          die($e->getMessage());
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
  
  private function getEnvName() {
        
    try {
      $this->consumeSpaceUntilTarget('{');
    } catch (\Exception $e) {
      die($e->getMessage() . " in environment declaration");
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

  private function consumeSpaceUntilTarget($target) {
          
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

  private function consumeUntilNonAlpha($from_cursor=true)
  {
    
    $char = $from_cursor ? $this->getChar() : $this->getNextChar();

    $alpha_text = '';

    while (!is_null($char) && ctype_alpha($char)) {
      $alpha_text .= $char;
      $char = $this->getNextChar();
    }
    
    return $alpha_text;
      
  }


}