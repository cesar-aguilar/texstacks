<?php

namespace TexStacks\Parsers;

class TextScanner {

  private int $line_number;
  private string $stream;
  private int $cursor = -1;
  private string|null $prev_char;
  private int $length = 0;

  public function setStream(string $latex_src, int $line_number = 1) {
    $this->line_number = $line_number;
    $this->stream = $latex_src;
    $this->length = strlen($this->stream);
  }

  /**
   *
   */
  public function forward()
  {
    $this->prev_char = $this->getChar();
    $this->cursor++;
    if ($this->getChar() === "\n") $this->line_number++;
  }

  /**
   * 
   */
  public function backup()
  {
    if ($this->getChar() === "\n") $this->line_number--;
    $this->cursor--;
    if ($this->cursor - 1 > -1) $this->prev_char = $this->stream[$this->cursor - 1];
  }
  
  /**
   *
   */
  public function getChar()
  {
    return $this->cursor < $this->length ? $this->stream[$this->cursor] : null;
  }

  /**
   *
   */
  public function getNextChar()
  {
    $this->prev_char = $this->getChar();
    $this->cursor++;
    $char = $this->getChar();

    if ($char === "\n") $this->line_number++;

    return $char;
  }

  /**
   *
   */
  public function getPrevChar()
  {
    return $this->prev_char;
  }

  /**
   *
   */
  public function peek()
  {
    return $this->cursor + 1 < $this->length ? $this->stream[$this->cursor + 1] : null;
  }

  public function lineNumber()
  {
    return $this->line_number;
  }

  /**
   *
   */
  public function getCommandContent($move_forward = false)
  {
    if ($move_forward) $this->forward();

    try {
      $this->consumeSpaceUntilTarget('{');
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    try {
      $content = $this->getContentUpToDelimiter('}', '{');
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    return $content;
  }

  /**
   * Get the content between two delimiters
   * Nesting is allowed.
   * 
   * The cursor should be just before $left delimiter
   * White space is ignored and is the only character allowed
   * before the $left delimiter, otherwise we break out of the loop
   * which indicates that the $left delimiter is not found.
   */
  public function getContentBetweenDelimiters($left_delim, $right_delim)
  {
    $content = '';
    $ALLOWED_CHARS = [' ', "\t", $left_delim];

    while (!is_null($char = $this->getNextChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $this->backup();
        break;
      }

      if ($this->is_space($char)) continue;

      if ($char === $left_delim) {

        try {
          $content = $this->getContentUpToDelimiter($right_delim, $left_delim);
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }

        break;
      }
    }

    return $content;
  }

  /**
   * Returns the content up to the next matching delimiter
   * 
   * Call this function if the current char is $left_delimiter
   * and you wish to consume (and return) the content
   * up to the next $right_delimiter.  The content may
   * contain nested $left_delimiter and $right_delimiter.  Upon
   * success, the cursor will be at the $right_delimiter.
   */
  public function getContentUpToDelimiter($right_delimiter, $left_delimiter)
  {
    $content = '';

    $delim_count = 1;

    $char = $this->getNextChar();

    while (!is_null($char) && $delim_count > 0) {

      if ($char === "\n" && $this->prev_char === "\n") {
        $so_far = $left_delimiter . $content;
        $message = "$so_far <--- Parse error on line {$this->line_number}: newline invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . "($right_delimiter)";
        throw new \Exception($message);
      }

      if ($char !== $right_delimiter) {

        $content .= $char;

        if ($char === $left_delimiter && $this->prev_char !== "\\") $delim_count++;

        $char = $this->getNextChar();
      } else {

        if ($this->prev_char !== "\\") $delim_count--;

        if ($delim_count > 0) {
          $content .= $right_delimiter;
          $char = $this->getNextChar();
        }
      }
    }

    if ($this->cursor === $this->length) {
      $so_far = $left_delimiter . $content;
      $message = "$so_far <--- Parse error on line {$this->line_number}: missing $right_delimiter";
      $message .= "<br>Function: " . __FUNCTION__ . "($right_delimiter, $left_delimiter)";
      throw new \Exception($message);
    }

    return $content;
  }

  /**
   *
   */
  public function getContentUpToDelimiterNoNesting($right_delimiter, $left_delimiter)
  {
    $content = '';

    $char = $this->getNextChar();

    while (!is_null($char) && $char !== $right_delimiter) {

      if ($char === "\n" && $this->prev_char === "\n") {
        $so_far = $left_delimiter . $content;
        $message = "$so_far <--- Parse error on line {$this->line_number}: newline invalid syntax";
        $message .= "<br>Function: " . __FUNCTION__ . "($right_delimiter)";
        throw new \Exception($message);
      }

      $content .= $char;

      $char = $this->getNextChar();
    }

    if ($this->cursor === $this->length) {
      $so_far = $left_delimiter . $content;
      $message = "$so_far <--- Parse error on line {$this->line_number}: missing $right_delimiter";
      $message .= "<br>Function: " . __FUNCTION__ . "($right_delimiter)";
      throw new \Exception($message);
    }

    return $content;
  }

  /**
   *
   */
  public function consumeEnvName()
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

    if ($this->cursor === $this->length) {
      throw new \Exception("Expected } at line {$this->line_number}");
    }

    return $env;
  }

  /**
   *
   */
  public function consumeSpaceUntilTarget($target)
  {

    if ($this->cursor === $this->length) {
      throw new \Exception("Unexpected end of file on line {$this->line_number}");
    }

    $char = $this->getChar();

    while (!is_null($char) && $this->is_space($char)) {
      $char = $this->getNextChar();
    }

    if ($char === $target || is_null($char)) {
      return;
    }

    throw new \Exception("Parse error: missing $target on line {$this->line_number}");
  }

  /**
   * Consume white space from current cursor position
   * 
   * After this method is called, the cursor will be at the first
   * non white space character
   */
  public function consumeWhiteSpace($backup = false)
  {

    if ($this->cursor === $this->length) {
      return;
    }

    $char = $this->getChar();

    $consumed = false;

    while (!is_null($char) && $this->is_space($char)) {
      $char = $this->getNextChar();
      $consumed = true;
    }

    if ($consumed && $backup) {
      $this->backup();
    }

  }

  /**
   * Consumes and returns all alphabetic characters.
   * After running the method, the cursor will be at
   * a non-alphabetic character
   */
  public function consumeUntilNonAlpha($from_cursor = true)
  {

    $char = $from_cursor ? $this->getChar() : $this->getNextChar();

    $alpha_text = '';

    while (!is_null($char) && (ctype_alpha($char) || $char === '*' || $char === '@')) {
      $alpha_text .= $char;
      $char = $this->getNextChar();
    }

    return $alpha_text;
  }

  /**
   *
   */
  public function consumeUntilTarget($target)
  {

    if ($this->cursor === $this->length) {
      throw new \Exception("Unexpected end of file on line {$this->line_number}");
    }

    $char = $this->getChar();

    while (!is_null($char) && $char !== $target) {
      $char = $this->getNextChar();
    }

    if ($char === $target || is_null($char)) {
      return;
    }

    throw new \Exception("Parse error: missing $target on line {$this->line_number}");
  }

  public function is_space($char) {
    return $char === ' ' || $char === "\t";
  }

}