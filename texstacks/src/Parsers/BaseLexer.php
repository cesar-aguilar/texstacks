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

      // If math inline or display environment
      if (in_array($char, ['(', ')', '[', ']'])) {
        $this->tokenizer->addControlSymbol($char);
        continue;
      }

      /* After setting the command for command names that contain only alphabetic
      characters, the cursor will be a non-alphabetic character */
      $this->tokenizer->setCommandName();

      $this->tokenizer->env = null;

      if ($this->tokenizer->commandIsEnv()) {
        try {
          $this->tokenizer->setEnvName();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
        }
      }

      $commandGroup = self::$library->getCommandGroup($this->tokenizer->command_name, $this->tokenizer->env);

      // When command/environment is not contained in any command group
      if (!$commandGroup) {
        $this->tokenizer->addUnregisteredToken(self::$library->defaultEnv());
        continue;
      }

      // Just in case the name of the command is a registered environment name in commandGroup
      // This should be deleted: May 18, 2023
      // if ($commandGroup::is_env() && is_null($this->tokenizer->env)) continue;

      // Short circuit if ending an environment
      if ($this->tokenizer->commandIsEndEnv()) {
        $this->tokenizer->addEnvToken($commandGroup::type());
        continue;
      }

      // Get signature of command/environment to get the rest of the token data
      $signature = $commandGroup::signature($this->tokenizer->command_name);

      // If we are at a begin env and env contains an optional argument then we need to
      // move forward because the cursor is at the } character of \begin{env-name}
      if(!is_null($this->tokenizer->env) && $signature && $signature[0] === '[' && $signature[1] === ']') $this->tokenizer->forward();

      // Make the token for the command/environment
      try {
        $token = $commandGroup::make($this->tokenizer->getTokenData($signature));
      } catch (\Exception $e) {
        $message = $this->getErrorMessage($e, $commandGroup);
        throw new \Exception($message);
      }

      // Now add the token.  If the token was for a custom macro, we need
      // to tokenize the token body which contains the custom macro definition
      if ($commandGroup::isCustomMacro()) {
        $subTokens = self::recursiveTokenize($token->body, $this->tokenizer->lineNumber());
        $this->tokenizer->addTokens($subTokens);
      } else {
        $this->tokenizer->addTokens($token);
      }

      // Backup if token is a command with no signature because the
      // cursor is at the character after the command name
      if ($signature === '' && is_null($this->tokenizer->env)) $this->tokenizer->backup();

      // Update the library if token affects how other tokens are generated
      // For example, \newcommand affects how the new command is tokenized
      if (self::$library->isUpdatable($token->command_name)) self::$library->update($token);

    }

    $this->tokenizer->postProcessTokens($this->is_recursive);

    return $this->tokenizer->getTokens();
  }

  /**
   *
   */
  private function getErrorMessage($e, $commandGroup): string
  {
    $message = $e->getMessage();
    $message .= "<br>Command: " . $this->tokenizer->command_name;
    $message .= "<br>$commandGroup";
    $message .= "<br>Line Number: " . $this->tokenizer->lineNumber();
    $message .= "<br>File: " . __FILE__;
    $message .= "<br>Code line: " . __LINE__;

    return $message;
  }
  
}
