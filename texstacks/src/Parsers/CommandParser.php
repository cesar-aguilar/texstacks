<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\TextScanner;

class CommandParser {

  private TextScanner $reader;

  public function __construct($latex_src, $line_number = 1)
  {
    $this->reader = new TextScanner();
    $this->reader->setStream($latex_src, $line_number);
  }

  /**
   *
   */
  public function getCmdWithArgOptions($command_name)
  {
    $content = '';
    $options = '';
    $src = '\\' . $command_name;
    $ARGS_DONE = false;

    $ALLOWED_CHARS = [' ', "\t", '{', '['];

    while (!is_null($char = $this->reader->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        if (!$ARGS_DONE) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax");
        }
        $this->reader->backup();
        break;
      }

      if ($this->reader->is_space($char)) {
        $this->reader->forward();
        continue;
      }

      if ($char === '[') {

        if (!$ARGS_DONE) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax");
        }

        try {
          $options = $this->reader->getContentUpToDelimiter(']', '[');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        break;
      }

      if ($char === '{') {

        try {
          $content = $this->reader->getCommandContent();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '{' . $content . '}';

        $ARGS_DONE = true;
      }
    }

    return [$content, $options];
  }

  /**
   *
   */
  public function getCmdWithOptionsArg($command_name, $signature)
  {

    if (str_contains($signature, '+')) $this->reader->forward();

    $content = '';
    $options = '';
    $src = '\\' . $command_name;
    $OPTIONS = false;

    $ALLOWED_CHARS = [' ', "\t", '{', '['];

    while (!is_null($char = $this->reader->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        throw new \Exception("$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax");
      }

      if ($this->reader->is_space($char)) {
        $this->reader->forward();
        continue;
      }

      if ($char === '[') {

        if ($OPTIONS) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax");
        }

        try {
          $options = $this->reader->getContentUpToDelimiter(']', '[');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        $this->reader->forward();
        $OPTIONS = true;
        continue;
      }

      if ($char === '{') {

        try {
          $content = $this->reader->getCommandContent();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '{' . $content . '}';

        break;
      }
    }

    return [$content, $options];
  }

  /**
   *
   */
  public function getCmdWithOptions($signature)
  {
    if (str_contains($signature, '+')) $this->reader->forward();

    $options = '';

    $ALLOWED_CHARS = [' ', "\t", '['];

    while (!is_null($char = $this->reader->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $this->reader->backup();
        break;
      }

      if ($this->reader->is_space($char)) {
        $this->reader->forward();
        continue;
      }

      if ($char === '[') {

        try {
          $options = $this->reader->getContentUpToDelimiter(']', '[');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        break;
      }
    }

    return $options;
  }

  /**
   *
   */
  public function getNewCommandData($command_name)
  {
    $command = '';
    $params = null;
    $default_param = null;
    $definition = '';
    $src = '\\' . $command_name;
    $HAS_PARAMS = false;
    $GOT_COMMAND = false;

    $ALLOWED_CHARS = [' ', "\t", '{', '[', "\n", '%', "\\"];

    $this->reader->backup();

    while (!is_null($char = $this->reader->getNextChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        $message = "$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }

      if ($char === "\n" && $this->reader->getPrevChar() === "\n") {
        $message = "$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }

      if ($char === '%') {
        $this->reader->consumeLine();
        continue;
      }

      if ($this->reader->is_space($char)) continue;

      if ($char === "\\" && $GOT_COMMAND) {
        $message = "$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }

      if ($char === "\\" && !$GOT_COMMAND) {
        try {
          $command = $this->reader->consumeUntilNonAlpha(from_cursor: false);
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }
        $command = '\\' . $command;
        $src .= $command;
        $GOT_COMMAND = true;
        $this->reader->backup();
        continue;
      }

      if ($char === '[') {

        if (!$GOT_COMMAND) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax");
        }

        try {
          $options = $this->reader->getContentUpToDelimiter(']', '[');
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
          $content = $this->reader->getContentUpToDelimiter('}', '{');
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

  /**
   *
   */
  public function getNewEnvironmentData($command_name)
  {
    $command = '';
    $params = null;
    $default_param = null;
    $begin_defn = '';
    $end_defn = '';
    $src = '\\' . $command_name;
    $HAS_PARAMS = false;
    $GOT_COMMAND = false;
    $GOT_BEGIN = false;

    $ALLOWED_CHARS = [' ', "\t", '{', '[', "\n", '%'];

    $this->reader->backup();

    while (!is_null($char = $this->reader->getNextChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        $message = "$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }

      if ($char === "\n" && $this->reader->getPrevChar() === "\n") {
        $message = "$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }

      if ($char === '%') {
        $this->reader->consumeLine();
        continue;
      }

      if ($this->reader->is_space($char)) continue;

      if ($char === '[') {

        if (!$GOT_COMMAND) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax");
        }

        try {
          $options = $this->reader->getContentUpToDelimiter(']', '[');
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
          $content = $this->reader->getContentUpToDelimiter('}', '{');
        } catch (\Exception $e) {
          throw new \Exception("$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax");
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

  /**
   *
   */
  public function getNewTheoremData($command_name)
  {
    $thm_name = '';
    $thm_heading = '';
    $use_counter = null;
    $number_within = null;
    $src = '\\' . $command_name;
    $GOT_COUNTER = false;
    $GOT_NAME = false;
    $GOT_HEADING = false;

    $ALLOWED_CHARS = [' ', "\t", '{', '[', '%'];

    $this->reader->backup();

    while (!is_null($char = $this->reader->getNextChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {

        if (!$GOT_NAME || !$GOT_HEADING) {
          $src .= $char;
          $message = "$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax";
          $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
          throw new \Exception($message);
        }

        $this->reader->backup();
        break;
      }

      if ($char === '%') {
        $this->reader->consumeLine();
        continue;
      }

      if ($this->reader->is_space($char)) continue;

      if ($char === '[') {

        if (!$GOT_NAME) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax");
        }

        try {
          $options = $this->reader->getContentUpToDelimiter(']', '[');
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

        throw new \Exception("$src <--- Parse error on line {$this->reader->lineNumber()}: Missing name or theorem heading in newtheorem command");
      }

      if ($char === '{') {

        try {
          $content = $this->reader->getContentUpToDelimiter('}', '{');
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

  /**
   *
   */
  public function getCommandArgs($command_name, $signature)
  {
    $has_options = str_contains($signature, '[]');
    $num_args = substr_count($signature, '{}');

    $args = [];
    $options = null;
    $src = '\\' . $command_name;
    $GOT_OPTIONS = false;

    $ALLOWED_CHARS = [' ', "\t", '%', "\n"];

    if ($has_options) {
      $ALLOWED_CHARS[] = '[';
    }

    if ($num_args) {
      $ALLOWED_CHARS[] = '{';
    }

    $char = $this->reader->getChar();

    while (!is_null($char)) {

      if (!in_array($char, $ALLOWED_CHARS)) {

        $src .= $char;
        $message = "$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }
      //
      else if ($char === "\n" && $this->reader->getPrevChar() === "\n") {
        $message = "$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . " in Code line: " . __LINE__;
        throw new \Exception($message);
      }
      //
      else if ($char === '%') {
        $this->reader->consumeLine();
      }
      //
      else if ($this->reader->is_space($char)) {
        continue; // not needed but for readability
      }
      //
      else if ($char === '[') {

        if (!$has_options || $GOT_OPTIONS || !empty($args)) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax");
        }

        try {
          $options = $this->reader->getContentUpToDelimiter(']', '[');
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        $src .= '[' . $options . ']';

        $GOT_OPTIONS = true;
      }
      //
      else if ($char === '{') {

        try {
          $content = $this->reader->getContentUpToDelimiter('}', '{');
        } catch (\Exception $e) {
          throw new \Exception("$src <--- Parse error on line {$this->reader->lineNumber()}: invalid syntax");
        }

        $src .= '{' . $content . '}';

        $args[] = $content;

        if (count($args) === $num_args) break;
      }

      $char = $this->reader->getNextChar();
    }

    return [$args, $options];
  }

  /**
   *
   */
  public function getAccentData()
  {
    $this->reader->forward();
    $this->reader->consumeWhiteSpace();

    if ($this->reader->getChar() === '{') {

      try {
        $content = ltrim($this->reader->getCommandContent());
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }

      $letter = $content[0];
      $tail = substr($content, 1);

    } else {
      $letter = $this->reader->getChar();
      $tail = '';
    }

    return [$letter, $tail];

  }

}