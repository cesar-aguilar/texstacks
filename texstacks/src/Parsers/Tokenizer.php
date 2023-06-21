<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Token;
use TexStacks\Parsers\CommandParser;
use TexStacks\Parsers\TextScanner;

class Tokenizer extends TextScanner
{

  private array $ACCENT_CMDS = [
    "'" => 'acute',
    "`" => 'grave',
    "^" => 'circ',
    '"' => 'uml',
    '~' => 'tilde',
  ];

  private string $buffer = '';
  private array $tokens = [];
  private bool $in_inlinemath = false;
  private bool $in_displaymath = false;

  private CommandParser $cmdParser;

  public string $command_name;
  public string|null $env;

  public function __construct($latex_src, $line_number = 1)
  {
    $this->setStream($latex_src, $line_number);
    $this->cmdParser = new CommandParser($latex_src, $line_number);
  }

  public function getTokens() {
    return $this->tokens;
  }

  public function addToBuffer(string $text)
  {
    $this->buffer .= $text;
  }

  private function addToken(Token $token)
  {
    $this->addBufferAsToken();

    $this->tokens[] = $token;
  }

  public function addTokens(array|Token $tokens)
  {

    if (is_array($tokens)) {
      foreach ($tokens as $token) {
        $this->addToken($token);
      }
    } else {
      $this->addToken($tokens);
    }

  }

  public function getLastToken()
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

  public function getTokenData($signature)
  {

    try {

      if ($signature === '{}' || $signature === '+{}') {

        $content = $this->getCommandContent(move_forward: str_contains($signature, '+'));
        // if (!is_null($env)) $args = [$content];
        $args = [$content];

      } else if ($signature === '[]') {

        $options = $this->getCmdWithOptions($signature);

      } else if ($signature === '{}[]') {

        list($content, $options) = $this->getCmdWithArgOptions();

      } else if ($signature === '{}[][]{}') {

        list($content, $params, $default_param, $defn) = $this->getNewCommandData();
        $args = [$params, $default_param, $defn];

      } else if ($signature === '{}[][]{}{}') {

        list($content, $params, $default_param, $begin_defn, $end_defn) = $this->getNewEnvironmentData();
        $args = [$params, $default_param, $begin_defn, $end_defn];

      } else if ($signature === '{}[]{}[]') {

        list($arg1, $options1, $arg2, $options2) = $this->getNewTheoremData();
        $args = [$arg1, $options1, $arg2, $options2];
        $content = $arg1;

      } else if ($signature === '^') {

        list($content, $options) = $this->getAccentData();

      } else if ($signature === '[]{}') {

        list($content, $options) = $this->getCmdWithOptionsArg($signature);
        // if (!is_null($env)) $args = [$content];
        $args = [$content];

      } else if ($signature === '+[]') {

        $options = $this->getCmdWithOptions($signature);

      } else if (in_array($signature, ['{}{}', '{}{}{}', '[]{}{}', '[]{}{}{}', '{}{}{}{}'])) {

        list($args, $options) = $this->getCommandArgs($signature);

      }

    } catch (\Exception $e) {
      $msg = $e->getMessage() . "<br>Function: " . __FUNCTION__ . " on Line: " . __LINE__;
      $msg .= "<br>&nbsp;&nbsp; Called as " . __CLASS__ . "->" . __FUNCTION__ . "('$signature')";
      $msg .= "<br>&nbsp;&nbsp; Command name: " . $this->command_name;
      $msg .= "<br>&nbsp;&nbsp; Environment name: " . $this->env;
      throw new \Exception($msg);
    }

    return [
      'command_name' => $this->command_name,
      'command_content' => $this->env ?? $content ?? null,
      'command_args' => $args ?? [],
      'command_options' => $options ?? null,
      'line_number' => $this->lineNumber(),
    ];
  }

  public function addDollarMath()
  {

    if ($this->peek() === "$") {
      $this->addDisplayMathToken();
    } else {
      $this->addInlineMathToken();
    }

  }

  public function addGroupEnvToken($char)
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
      'line_number' => $this->lineNumber(),
    ]);
  }

  public function addEnvToken($tokenType) {

    // $this->command_name is either begin or end

    $this->addToken(new Token([
      'type' => $tokenType,
      'command_name' => $this->command_name,
      'command_content' => $this->env,
      'command_src' => "\\" . $this->command_name . "{" . $this->env . "}",
      'line_number' => $this->lineNumber(),
    ]));

  }

  // public function addControlSymbol($char)
  // {
  //   if ($char === '(' || $char === ')') {
  //     $this->addInlineMathToken($char);
  //   }
  //   else if ($char === '[' || $char === ']') {
  //     $this->addDisplayMathToken($char);
  //   }
  // }

  public function addUnregisteredToken($tokenType) {

    if (is_null($this->env)) {
      // Add unknown command to buffer
      $this->addToBuffer("\\" . $this->command_name);
      // Backup because cursor is one after the command name
      $this->backup();
      return;
    }

    // If we get here then we have an unknown environment (begin or end)
    $this->addEnvToken($tokenType);

  }

  public function setCommandName() {

    if (in_array($this->getChar(), ['(', ')', '[', ']'])) {
      $this->command_name = $this->getChar();
      return;
    }

    // Handles accents
    if ($this->isAccent($this->getChar())) {
      $this->command_name = $this->getChar();
      return;
    }

    // Handles control symbols
    if (! ctype_alpha($this->getChar()) && $this->getChar() !== '@' && $this->getChar() !== '*') {
      $this->command_name = $this->getChar();
      return;
    }

    // Handles alpha commands (regex [a-zA-Z@]+)
    $this->command_name = $this->consumeUntilNonAlpha();

  }

  public function setEnvName() {
    $this->env = $this->consumeEnvName();
  }

  public function consumeLatexComment() {

    if ($this->peek() !== '@') {
      $this->consumeLine();
      return;
    }

    $this->forward();

    if ($this->peek() === '=') {
      // Found %@=
      $this->forward();
      $this->moveToTarget("%@=");
    }

  }

  public function commandIsEnv() {
    return $this->command_name === 'begin' || $this->command_name === 'end';
  }

  public function commandIsEndEnv() {
    return !is_null($this->env) && $this->command_name === 'end';
  }

  public function postProcessTokens($is_recursive) {

    // Important: Add anything left in the buffer as text Token
    $this->addBufferAsToken();

    /* This is a hack to trim white space/newlines
     * before and after section headings and environments
     */
    foreach ($this->tokens as $k => $token) {

      if ($token->type !== 'text') continue;

      if ($k === count($this->tokens) - 1) continue;

      if ($k === 0) {
        $this->tokens[$k]->body = rtrim($token->body, "\n");
        continue;
      }

      $next_type = $this->tokens[$k + 1]->type;

      if ((str_contains($next_type, 'environment') || $next_type == 'cmd:section') && $next_type !== 'environment:group' && $next_type !== 'environment:inlinemath') {
        $this->tokens[$k]->body = rtrim($token->body);
      }

      $prev_type = $this->tokens[$k - 1]->type;

      if ((str_contains($prev_type, 'environment') || $prev_type == 'cmd:section') && $prev_type !== 'environment:group') {
        $this->tokens[$k]->body = ltrim($token->body);
      }
    }

    if (!$is_recursive) {
      // $this->dumpTokensLineRange(39, 43);
      // $this->dumpTokensOfType([
      //   \TexStacks\Commands\Core\DisplayMathCommand::type(),
      // ]);
    }

  }

  public function isAccent($char)
  {
    return key_exists($char, $this->ACCENT_CMDS);
  }

  /*  PRIVATE METHODS */

  private function addBufferAsToken()
  {

    if ($this->buffer === '') return;

    // Replace more than two newlines with two newlines
    // $text = preg_replace('/\n{3,}/', "\n\n", $this->buffer);
    $text = $this->buffer;

    $this->tokens[] = new Token([
      'type' => 'text',
      'body' => $text,
      'line_number' => $this->lineNumber(),
    ]);

    $this->buffer = '';
  }

  private function addInlineMathToken($char = null)
  {

    if ($char === null) {
      $this->in_inlinemath = !$this->in_inlinemath;
      $char = $this->in_inlinemath ? '(' : ')';
    }

    // Treat inline math like a begin/end environment
    $this->addToken(new Token([
      'type' => 'inlinemath',
      'command_name' => $char === '(' ? 'begin' : 'end',
      'command_content' => 'inlinemath',
      'command_options' => '',
      'command_src' => "\\" . $char,
      'line_number' => $this->lineNumber(),
    ]));
  }

  private function addDisplayMathToken($char = null)
  {

    if ($char === null) {
      $this->forward();
      $this->in_displaymath = !$this->in_displaymath;
      $char = $this->in_displaymath ? '[' : ']';
    }

    // Treat display math like a begin/end environment
    $cmd = $char === '[' ? 'begin' : 'end';

    $this->addToken(new Token([
      'type' => 'environment:displaymath',
      'command_name' => $cmd,
      'command_content' => 'equation*',
      'command_src' => "\\" . $cmd . "{equation*}",
      'line_number' => $this->lineNumber(),
    ]));
  }

  /* Commands that get token data */

  private function getCmdWithArgOptions()
  {
    $content = '';
    $options = '';
    $src = '\\' . $this->command_name;
    $ARGS_DONE = false;

    $ALLOWED_CHARS = [' ', "\t", '{', '['];

    while (!is_null($char = $this->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        if (!$ARGS_DONE) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->lineNumber()}: invalid syntax");
        }
        $this->backup();
        break;
      }

      if ($this->is_space($char)) {
        $this->forward();
        continue;
      }

      if ($char === '[') {

        if (!$ARGS_DONE) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->lineNumber()}: invalid syntax");
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

  private function getCmdWithOptionsArg($signature)
  {

    if (str_contains($signature, '+')) $this->forward();

    $content = '';
    $options = '';
    $src = '\\' . $this->command_name;
    $OPTIONS = false;

    $ALLOWED_CHARS = [' ', "\t", '{', '['];

    while (!is_null($char = $this->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        throw new \Exception("$src <--- Parse error on line {$this->lineNumber()}: invalid syntax");
      }

      if ($this->is_space($char)) {
        $this->forward();
        continue;
      }

      if ($char === '[') {

        if ($OPTIONS) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->lineNumber()}: invalid syntax");
        }

        try {
          $options = $this->getContentUpToDelimiter(']', '[');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        $this->forward();
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

    $ALLOWED_CHARS = [' ', "\t", '['];

    while (!is_null($char = $this->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $this->backup();
        break;
      }

      if ($this->is_space($char)) {
        $this->forward();
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

  private function getNewCommandData()
  {
    $command = '';
    $params = null;
    $default_param = null;
    $definition = '';
    $src = '\\' . $this->command_name;
    $HAS_PARAMS = false;
    $GOT_COMMAND = false;

    $ALLOWED_CHARS = [' ', "\t", '{', '[', "\n", '%', "\\"];

    $this->backup();

    while (!is_null($char = $this->getNextChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        $message = "$src <--- Parse error on line {$this->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }

      if ($char === "\n" && $this->getPrevChar() === "\n") {
        $message = "$src <--- Parse error on line {$this->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }

      if ($char === '%') {
        $this->consumeLine();
        continue;
      }

      if ($this->is_space($char)) continue;

      if ($char === "\\" && $GOT_COMMAND) {
        $message = "$src <--- Parse error on line {$this->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
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
          throw new \Exception("$src <--- Parse error on line {$this->lineNumber()}: invalid syntax");
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

  private function getNewEnvironmentData()
  {
    $command = '';
    $params = null;
    $default_param = null;
    $begin_defn = '';
    $end_defn = '';
    $src = '\\' . $this->command_name;
    $HAS_PARAMS = false;
    $GOT_COMMAND = false;
    $GOT_BEGIN = false;

    $ALLOWED_CHARS = [' ', "\t", '{', '[', "\n", '%'];

    $this->backup();

    while (!is_null($char = $this->getNextChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        $message = "$src <--- Parse error on line {$this->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }

      if ($char === "\n" && $this->getPrevChar() === "\n") {
        $message = "$src <--- Parse error on line {$this->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }

      if ($char === '%') {
        $this->consumeLine();
        continue;
      }

      if ($this->is_space($char)) continue;

      if ($char === '[') {

        if (!$GOT_COMMAND) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->lineNumber()}: invalid syntax");
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
          throw new \Exception("$src <--- Parse error on line {$this->lineNumber()}: invalid syntax");
        }

        $src .= '{' . $content . '}';

        if (!$GOT_COMMAND) {
          $GOT_COMMAND = true;
          $command = $content;
        } else if (!$GOT_BEGIN) {
          $GOT_BEGIN = true;
          $begin_defn = $content;
        } else {
          $end_defn = $content;
          break;
        }
      }
    }

    return [$command, $params, $default_param, $begin_defn, $end_defn];
  }

  private function getNewTheoremData()
  {
    $thm_name = '';
    $thm_heading = '';
    $use_counter = null;
    $number_within = null;
    $src = '\\' . $this->command_name;
    $GOT_COUNTER = false;
    $GOT_NAME = false;
    $GOT_HEADING = false;

    $ALLOWED_CHARS = [' ', "\t", '{', '[', '%'];

    $this->backup();

    while (!is_null($char = $this->getNextChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {

        if (!$GOT_NAME || !$GOT_HEADING) {
          $src .= $char;
          $message = "$src <--- Parse error on line {$this->lineNumber()}: invalid syntax";
          $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
          throw new \Exception($message);
        }

        $this->backup();
        break;
      }

      if ($char === '%') {
        $this->consumeLine();
        continue;
      }

      if ($this->is_space($char)) continue;

      if ($char === '[') {

        if (!$GOT_NAME) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->lineNumber()}: invalid syntax");
        }

        try {
          $options = $this->getContentUpToDelimiter(']', '[');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        if (!$GOT_COUNTER && !$GOT_HEADING) {
          $use_counter = $options;
          $GOT_COUNTER = true;
          continue;
        }

        $number_within = $options;

        if ($GOT_NAME && $GOT_HEADING) break;

        throw new \Exception("$src <--- Parse error on line {$this->lineNumber()}: Missing name or theorem heading in newtheorem command");
      }

      if ($char === '{') {

        try {
          $content = $this->getContentUpToDelimiter('}', '{');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '{' . $content . '}';

        if (!$GOT_NAME) {
          $thm_name = $content;
          $GOT_NAME = true;
        } else {
          $thm_heading = $content;
          $GOT_HEADING = true;
        }

        continue;
      }
    }

    return [$thm_name, $use_counter, $thm_heading, $number_within];
  }

  private function getCommandArgs($signature)
  {
    $has_options = str_contains($signature, '[]');
    $num_args = substr_count($signature, '{}');

    $args = [];
    $options = null;
    $src = '\\' . $this->command_name;
    $GOT_OPTIONS = false;

    $ALLOWED_CHARS = [' ', "\t", '%', "\n"];

    if ($has_options) {
      $ALLOWED_CHARS[] = '[';
    }

    if ($num_args) {
      $ALLOWED_CHARS[] = '{';
    }

    $char = $this->getChar();

    while (!is_null($char)) {

      if (!in_array($char, $ALLOWED_CHARS)) {

        $src .= $char;
        $message = "$src <--- Parse error on line {$this->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }
      //
      else if ($char === "\n" && $this->getPrevChar() === "\n") {
        $message = "$src <--- Parse error on line {$this->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }
      //
      else if ($char === '%') {
        $this->consumeLine();
      }
      //
      else if ($this->is_space($char)) {
        continue; // not needed but for readability
      }
      //
      else if ($char === '[') {

        if (!$has_options || $GOT_OPTIONS || !empty($args)) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->lineNumber()}: invalid syntax");
        }

        try {
          $options = $this->getContentUpToDelimiter(']', '[');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        $GOT_OPTIONS = true;
      }
      //
      else if ($char === '{') {

        try {
          $content = $this->getContentUpToDelimiter('}', '{');
        } catch (\Exception $e) {
          throw new \Exception("$src <--- Parse error on line {$this->lineNumber()}: invalid syntax");
        }

        $src .= '{' . $content . '}';

        $args[] = $content;

        if (count($args) === $num_args) break;
      }

      $char = $this->getNextChar();
    }

    return [$args, $options];
  }

  private function getAccentData()
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

    } else {
      $letter = $this->getChar();
      $tail = '';
    }

    return [$letter, $tail];

  }

  private function dumpTokensLineRange($a, $b) {

    $this->tokens = array_filter($this->tokens, function ($token) use ($a, $b) {
      return $token->line_number >= $a and $token->line_number <= $b;
    });

    dd($this->tokens);

  }

  private function dumpTokensOfType($token_types) {

    $token_types = is_array($token_types) ? $token_types : [$token_types];

    $this->tokens = array_filter($this->tokens, function ($token) use ($token_types) {
      return in_array($token->type, $token_types);
    });

    dd($this->tokens);

  }

}
