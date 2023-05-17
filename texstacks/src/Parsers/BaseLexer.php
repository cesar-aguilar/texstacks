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
      // set the command name; cursor will be a non-alphabetic char
      // when complete
      $this->tokenizer->setCommandName();

      // Make token
      $this->tokenizer->env = null;

      if ($this->tokenizer->commandIsEnv()) {
        try {
          $this->tokenizer->setEnvName();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
        }
      }

      foreach (self::$library->getCommandGroups() as $ClassName) {

        if (!$ClassName::contains($this->tokenizer->env ?? $this->tokenizer->command_name)) continue;

        if ($ClassName::is_env() && is_null($this->tokenizer->env)) continue;

        if ($this->tokenizer->commandIsEndEnv()) {
          $this->tokenizer->addEnvToken($ClassName::type());
          continue 2;
        }

        // If we get here, then $ClassName contains the command or environment (begin)
        $signature = $ClassName::signature($this->tokenizer->command_name);

        // If we are at a begin env and env contains optional argument then we need to
        // move forward because the cursor is at the } character of \begin{env-name}
        if(!is_null($this->tokenizer->env) && $signature && $signature[0] === '[' && $signature[1] === ']') $this->tokenizer->forward();

        try {
          $token = $ClassName::make($this->tokenizer->getTokenData($signature));
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
        if ($signature === '' && is_null($this->tokenizer->env)) $this->tokenizer->backup();

        // Some tokens affect how other tokens are generated
        if (self::$library->isUpdatable($token->command_name)) self::$library->update($token);

        continue 2;
      }

      // If we get here then we have an unknown command/environment
      $this->tokenizer->addUnregisteredToken(self::$library->defaultEnv()::type());

    }

    $this->tokenizer->addBufferAsToken();

    $this->tokenizer->postProcessTokens($this->is_recursive);

    return $this->tokenizer->getTokens();
  }
  
}
