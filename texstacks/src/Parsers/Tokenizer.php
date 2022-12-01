<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Token;
use TexStacks\Parsers\TextScanner;

class Tokenizer extends TextScanner
{

  const ACCENT_CMDS = [
    "'" => 'acute',
    "`" => 'grave',
    "^" => 'circ',
    '"' => 'uml',
  ];

  protected array $tokens = [];
  protected bool $in_math = false;

  protected string $command_name;
  protected static array $ref_labels;
  protected static array $citations;

  public static function setRefLabels($labels)
  {
    self::$ref_labels = $labels;
  }

  public static function setCitations($citations)
  {
    self::$citations = $citations;
  }

  public function getCitations()
  {
    return self::$citations;
  }

  public function getRefLabels()
  {
    return self::$ref_labels;
  }

  protected function addBufferAsToken()
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

  protected function addToken(Token $token)
  {

    $this->addBufferAsToken();

    $this->tokens[] = $token;
  }

  protected function getLastToken()
  {
    $count = count($this->tokens);

    return $count > 0 ? $this->tokens[$count - 1] : null;
  }

  public function prettyPrintTokens()
  {

    foreach ($this->tokens as $token) {
      echo $token;
    }
    die();
  }

  protected function getTokenData($signature, $env = null)
  {

    try {

      if ($signature === '{}' || $signature === '+{}') {
        $content = $this->getCommandContent(move_forward: str_contains($signature, '+'));
        if (!is_null($env)) $args = [$content];
      } else if ($signature === '+[]' || $signature === '[]') {
        $options = $this->getCmdWithOptions($signature);
      } else if ($signature === '{}[]') {
        list($content, $options) = $this->getCmdWithArgOptions();
      } else if ($signature === '[]{}' || $signature === '+[]{}') {
        list($content, $options) = $this->getCmdWithOptionsArg($signature);
        if (!is_null($env)) $args = [$content];
      } else if ($signature === '{}{}') {
        $args = $this->getCmdWithArgArg();
      } else if ($signature === '{}[][]{}') {
        list($content, $params, $default_param, $defn) = $this->getNewCommandData();
        $args = [$params, $default_param, $defn];
      }
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    return [
      'command_name' => $this->command_name,
      'command_content' => $env ?? $content ?? null,
      'command_args' => $args ?? [],
      'command_options' => $options ?? null,
      'line_number' => $this->line_number,
    ];
  }

  protected function getEndEnvTokenData($env)
  {

    return [
      'command_name' => $this->command_name,
      'command_content' => $env,
      'line_number' => $this->line_number,
    ];
  }

  protected function tokenizeCmdWithOptionsArg(string|null $type = null): Token
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
          $options = $this->getContentUpToDelimiter(']', '[');
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

    $token = new Token([
      'type' => 'cmd:' . $this->command_name,
      'command_name' => $this->command_name,
      'command_content' => $content,
      'command_options' => $options,
      'command_src' => $src,
      'body' => $content,
      'line_number' => $this->line_number,
    ]);

    if ($this->command_name === 'cite') $this->tokenizeCitation($token);

    return $token;
  }

  protected function tokenizeCmdWithOptions(string|null $type = null): Token
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
          $options = $this->getContentUpToDelimiter(']', '[');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        break;
      }
    }

    return new Token([
      'type' => 'cmd:' . $this->command_name,
      'command_name' => $this->command_name,
      'command_content' => '',
      'command_options' => $options,
      'command_src' => $src,
      'line_number' => $this->line_number,
    ]);
  }

  protected function tokenizeCmdWithArgOptions(string|null $type = null): Token
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
          $options = $this->getContentUpToDelimiter(']', '[');
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

  protected function addInlineMathToken($char)
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

  protected function addDisplayMathToken($char)
  {
    // Treat display math like a begin/end environment
    $cmd = $char === '[' ? 'begin' : 'end';

    $this->addToken(new Token([
      'type' => 'environment:displaymath',
      'command_name' => $cmd,
      'command_content' => 'equation*',
      'command_src' => "\\" . $cmd . "{equation*}",
      'line_number' => $this->line_number,
    ]));
  }

  protected function tokenizeSection(): Token
  {

    $content = '';
    $options = '';
    $src = '\\' . $this->command_name;
    $TOC_ENTRY = false;

    $ALLOWED_CHARS = [' ', '{', '['];

    while (!is_null($char = $this->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid sectioning command");
      }

      if ($char === ' ') {
        $this->cursor++;
        continue;
      }

      if ($char === '[') {

        if ($TOC_ENTRY) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        try {
          $options = $this->getContentUpToDelimiter(']', '[');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        $this->cursor++;
        $TOC_ENTRY = true;
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
      'type' => 'cmd:section',
      'command_name' => $this->command_name,
      'command_content' => $content,
      'command_options' => $options,
      'command_src' => $src,
      'line_number' => $this->line_number,
    ]);
  }

  private function getCmdWithArgOptions(string|null $type = null)
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
          $options = $this->getContentUpToDelimiter(']', '[');
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

    return [$content, $options];
  }

  private function getCmdWithOptionsArg($signature, string|null $type = null)
  {

    if (str_contains($signature, '+')) $this->forward();

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
          $options = $this->getContentUpToDelimiter(']', '[');
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

    return [$content, $options];
  }

  private function getCmdWithOptions($signature)
  {
    if (str_contains($signature, '+')) $this->forward();

    $options = '';

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
          $options = $this->getContentUpToDelimiter(']', '[');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        break;
      }
    }

    return $options;
  }

  private function getCmdWithArgArg()
  {
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

    return [$arg_1, $arg_2];
  }

  private function getNewCommandData()
  {
    $command = '';
    $params = null;
    $default_param = null;
    $definition = '';
    $src = '\\' . $this->command_name;
    $HAS_PARAMS = false;
    $GOT_COMMAND = false;

    $ALLOWED_CHARS = [' ', '{', '[', "\n", '%', "\\"];

    $this->backup();

    while (!is_null($char = $this->getNextChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
      }

      if ($char === "\n" && $this->prev_char === "\n") {
        throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
      }

      if ($char === '%') {
        $this->consumeUntilTarget("\n");
        continue;
      }

      if ($char === ' ') continue;

      if ($char === "\\" && $GOT_COMMAND) {
        throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
      }

      if ($char === "\\" && !$GOT_COMMAND) {
        try {
          $command = $this->consumeUntilNonAlpha(from_cursor: false);
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }
        $command = '\\' . $command;
        $src .= $command;
        $GOT_COMMAND = true;
        $this->backup();
        continue;
      }

      if ($char === '[') {

        if (!$GOT_COMMAND) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        try {
          $options = $this->getContentUpToDelimiter(']', '[');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        if (!$HAS_PARAMS) {
          $HAS_PARAMS = true;
          $params = $options;
        } else {
          $default_param = $options;
        }
        continue;
      }

      if ($char === '{') {

        try {
          $content = $this->getContentUpToDelimiter('}', '{');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '{' . $content . '}';

        if ($GOT_COMMAND) {
          $definition = $content;
          break;
        }

        $GOT_COMMAND = true;
        $command = $content;

      }
    }

    return [$command, $params, $default_param, $definition];

  }

  protected function addGroupEnvToken($char)
  {
    $this->addBufferAsToken();

    $command_name = $char === '{' ? 'begin' : 'end';

    $command_content = 'unnamed';

    $this->tokens[] = new Token([
      'type' => 'environment:group',
      'command_name' => $command_name,
      'command_content' => $command_content,
      'command_src' => '',
      'command_options' => '',
      'line_number' => $this->line_number,
    ]);
  }

  protected function addSymbolToken(string $char)
  {

    // Move one character forward and
    // see if there are any options, this handles
    // the commands like \\[1cm]
    $this->getNextChar();

    try {
      $token = $this->tokenizeCmdWithOptions();
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    $token->type = 'cmd:symbol';
    $token->body = $char;

    $this->addToken($token);
  }

  protected function addAccentToken($char)
  {
    $this->forward();
    $this->consumeWhiteSpace();

    if ($this->getChar() === '{') {

      try {
        $content = ltrim($this->getCommandContent());
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }

      $letter = $content[0];
      $tail = substr($content, 1);
      $command_src = "\\" . $char . "{" . $letter . "}";
    } else {
      $letter = $this->getChar();
      $tail = '';
      $command_src = "\\" . $char . $letter;
    }

    if (!in_array($letter, ['a', 'e', 'i', 'o', 'u', 'y', 'A', 'E', 'I', 'O', 'U', 'Y'])) {
      $this->buffer .= $command_src . $tail;
      return;
    }

    $accent = self::ACCENT_CMDS[$char];

    $body = "&$letter$accent;";

    $this->addToken(new Token([
      'type' => 'cmd:accent',
      'command_name' => $char,
      'command_content' => $letter,
      'command_src' => $command_src,
      'body' => $body,
      'line_number' => $this->line_number,
    ]));

    if ($tail) {
      $this->addToken(new Token([
        'type' => 'text',
        'body' => $tail,
        'line_number' => $this->line_number,
      ]));
    }
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

  // private function addInlineMath($delim)
  // {

  //   try {
  //     if ($delim === '$') {
  //       $content = $this->getContentUpToDelimiterNoNesting('$', '$');
  //     } else {
  //       $content = $this->getMathContent(')');
  //     }
  //   } catch (\Exception $e) {
  //     throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
  //   }

  //   $this->addToken(new Token([
  //     'type' => 'inlinemath',
  //     'body' => $content,
  //     'command_src' => '\(' . $content . '\)',
  //     'line_number' => $this->line_number,
  //   ]));

  //   // $this->addInlineMathToken('(');
  //   // $this->addToken(new Token([
  //   //   'type' => 'text',
  //   //   'body' => $content,
  //   //   'line_number' => $this->line_number,
  //   // ]));
  //   // $this->addInlineMathToken(')');
  // }

  // private function addDisplayMath($delim)
  // {

  //   try {
  //     if ($delim === '$$') {
  //       $content = $this->getContentInDoubleDollarSign();
  //     } else {
  //       $content = $this->getMathContent(']');
  //     }
  //   } catch (\Exception $e) {
  //     throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
  //   }

  //   $this->addToken(new Token([
  //     'type' => 'environment:displaymath',
  //     'command_name' => 'begin',
  //     'command_content' => 'equation*',
  //     'command_src' => "\\begin{equation*}",
  //     'line_number' => $this->line_number,
  //   ]));
  //   $this->addToken(new Token([
  //     'type' => 'text',
  //     'body' => $content,
  //     'line_number' => $this->line_number,
  //   ]));
  //   $this->addToken(new Token([
  //     'type' => 'environment:displaymath',
  //     'command_name' => 'end',
  //     'command_content' => 'equation*',
  //     'command_src' => "\\end{equation*}",
  //     'line_number' => $this->line_number,
  //   ]));
  // }

  /**
   * Get content upto \] or \)
   *
   * $delim is either ] or )
   */
  // private function getMathContent($delim)
  // {

  //   $content = '';

  //   $left = $delim === ']' ? '[' : '(';

  //   $char = $this->getNextChar();

  //   while (!is_null($char) && !($char === "\\" && $this->peek() === $delim)) {

  //     if ($char === "\n" && $this->prev_char === "\n") {
  //       $so_far = "&#92;$left" . $content;
  //       $message = "$so_far <--- Parse error on line {$this->line_number}: newline invalid syntax";
  //       $message .= "<br>Function: " . __FUNCTION__ . "($delim)";
  //       throw new \Exception($message);
  //     }

  //     $content .= $char;

  //     $char = $this->getNextChar();
  //   }

  //   if (is_null($char)) {
  //     $so_far = "&#92;$left" . $content;
  //     $message = "$so_far <--- Parse error on line {$this->line_number}: display math should end with &#92;$delim";
  //     $message .= "<br>Function: " . __FUNCTION__ . "($delim)";
  //     throw new \Exception($message);
  //   }

  //   if ($char === "\\" && $this->getNextChar() !== $delim) {
  //     $so_far = "&#92;$left" . $content;
  //     $message = "$so_far <--- Parse error on line {$this->line_number}: display math should end with &#92;$delim";
  //     $message .= "<br>Function: " . __FUNCTION__ . "($delim)";
  //     throw new \Exception($message);
  //   }

  //   return $content;
  // }

  // private function getContentInDoubleDollarSign()
  // {
  //   $content = '';

  //   $char = $this->getNextChar();

  //   while (!is_null($char) && !($char === '$' && $this->peek() === '$')) {

  //     if ($char === "\n" && $this->prev_char === "\n") {
  //       $so_far = '\$\$' . $content;
  //       $message = "$so_far <--- Parse error on line {$this->line_number}: newline invalid syntax";
  //       $message .= "<br>Function: " . __FUNCTION__ . "()";
  //       throw new \Exception($message);
  //     }

  //     $content .= $char;

  //     $char = $this->getNextChar();
  //   }

  //   if (is_null($char)) {
  //     $so_far = '\$\$' . $content;
  //     $message = "$so_far <--- Parse error on line {$this->line_number}: display math should end with \$\$";
  //     $message .= "<br>Function: " . __FUNCTION__ . "()";
  //     throw new \Exception($message);
  //   }

  //   if ($char === '$' && $this->getNextChar() !== '$') {
  //     $so_far = '\$\$' . $content;
  //     $message = "$so_far <--- Parse error on line {$this->line_number}: display math should end with \$\$";
  //     $message .= "<br>Function: " . __FUNCTION__ . "()";
  //     throw new \Exception($message);
  //   }

  //   return $content;
  // }

}
