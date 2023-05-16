<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Tokenizer;

class BaseLexer
{

  private $tokenizer;
  private static $library;
  private bool $is_recursive;

  public function __construct(TokenLibrary $library = null, bool $is_recursive = false)
  {
    self::$library = self::$library ?? $library ?? null;
    $this->is_recursive = $is_recursive;
  }

  private static function recursiveTokenize($text, $line_number = 1) {

    $lexer = new self(is_recursive: true);

    try {
      $tokens = $lexer->tokenize($text, $line_number);
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    return $tokens;

  }

  public function tokenize(string $latex_src, int $line_number = 1)
  {

    if (trim($latex_src) === '') return [];

    $this->tokenizer = new Tokenizer($latex_src, $line_number);

    while (!is_null($char = $this->tokenizer->getNextChar())) {

      if ($char === '~') {
        $this->tokenizer->addToBuffer(' ');
        continue;
      }

      if ($char === '{' || $char === '}') {
        $this->tokenizer->addGroupEnvToken($char);
        continue;
      }

      if ($char === '%') {
        $this->tokenizer->consumeLatexComment();
        continue;
      }

      if ($char === "$") {
        $this->tokenizer->addDollarMath();
        continue;
      }

      if ($char !== "\\") {
        $this->tokenizer->addToBuffer($char);
        continue;
      }

      $char = $this->tokenizer->getNextChar();

      if (is_null($char)) {
        $this->tokenizer->addToBuffer("\\");
        break;
      }

      // If char is non-alphabetic then we have a control symbol
      if (!ctype_alpha($char ?? '')) {
        $this->tokenizer->addControlSymbol($char);
        continue;
      }

      // The current char is alphabetic so consume and
      // return the command name; cursor will be a non-alphabetic char
      // when complete
      $this->tokenizer->command_name = $this->tokenizer->consumeCommandName();

      // Make token
      $env = null;

      if ($this->tokenizer->command_name === 'begin' || $this->tokenizer->command_name === 'end') {
        try {
          $env = $this->tokenizer->consumeEnvName();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
        }
      }

      foreach (self::$library->getCommandGroups() as $ClassName) {

        if (!$ClassName::contains($env ?? $this->tokenizer->command_name)) continue;

        if ($ClassName::is_env() && is_null($env)) continue;

        if (!is_null($env) && $this->tokenizer->command_name === 'end') {
          $token = $ClassName::end($this->tokenizer->getEndEnvTokenData($env));
          $this->tokenizer->addToken($token);
          continue 2;
        }

        $signature = $ClassName::signature($this->tokenizer->command_name);

        // If begin env and env contains optional argument then need to move forward
        // because cursor is at the } character of \begin{env-name}
        if(!is_null($env) && $signature && $signature[0] === '[' && $signature[1] === ']') $this->tokenizer->forward();

        try {
          $token = $ClassName::make($this->tokenizer->getTokenData($signature, $env));
        } catch (\Exception $e) {
          $message = $e->getMessage();
          $message .= "<br>Command: " . $this->tokenizer->command_name;
          $message .= "<br>$ClassName";
          $message .= "<br>Line Number: " . $this->tokenizer->getLineNumber();
          $message .= "<br>File: " . __FILE__;
          $message .= "<br>Code line: " . __LINE__;
          throw new \Exception($message);
        }

        if ($ClassName::type() === 'cmd:custom-macro' ) {
          $subTokens = self::recursiveTokenize($token->body, $this->tokenizer->getLineNumber());
          $this->tokenizer->addTokens($subTokens);
        } else {
          $this->tokenizer->addToken($token);
        }

        // Backup if token is a command with no signature
        if ($signature === '' && is_null($env)) $this->tokenizer->backup();

        // Some tokens affect how other tokens are generated
        if (self::$library->isUpdatable($token->command_name)) self::$library->update($token);

        continue 2;
      }

      // If we get here then we have an unknown command
      if (is_null($env)) {
        $this->tokenizer->addUnknownCommandToBuffer();
        continue;
      }

      // If we get here then we have an unknown environment
      $token = $this->tokenizer->command_name === 'end'
        ? self::$library->defaultEnv()::end($this->tokenizer->getEndEnvTokenData($env))
        : self::$library->defaultEnv()::make($this->tokenizer->getTokenData('', $env));

      $this->tokenizer->addToken($token);
    }

    $this->tokenizer->addBufferAsToken();

    $this->tokenizer->postProcessTokens($this->is_recursive);

    return $this->tokenizer->getTokens();
  }
  
}
