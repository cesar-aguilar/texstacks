<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Tokenizer;

class BaseLexer
{

  private $tokenizer;
  private $line_number_offset;
  private $command_groups = [];
  private $default_env;
  protected $updatable_commands = [];

  public function __construct($data = [])
  {

    $this->line_number_offset = $data['line_number_offset'] ?? 1;

    $this->default_env = \TexStacks\Commands\Environment::class;
  }

  public function tokenize(string $latex_src)
  {

    if (trim($latex_src) === '') return [];

    $this->tokenizer = new Tokenizer($this->line_number_offset, $latex_src);
 
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

        if ($this->tokenizer->getNextChar() === "$") {
          try {
            $this->tokenizer->addDisplayMathToken();
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
          }
        } else {
          $this->tokenizer->backup();
          try {
            $this->tokenizer->addInlineMathToken();
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
          }
        }

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

        if ($char === '(' || $char === ')') {
          $this->tokenizer->addInlineMathToken($char);
          continue;
        }

        else if ($char === '[' || $char === ']') {
          $this->tokenizer->addDisplayMathToken($char);
          continue;
        }

        else if ($this->tokenizer->isAccent($char)) {
          $this->tokenizer->addAccentToken($char);
          continue;
        }

        else {
          $this->tokenizer->command_name = $char;
          $this->tokenizer->addSymbolToken($char);
          continue;
        }

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

      foreach ($this->command_groups as $ClassName) {

        if (!$ClassName::contains($env ?? $this->tokenizer->command_name)) continue;

        if ($ClassName::is_env() && is_null($env)) continue;

        if (!is_null($env) && $this->tokenizer->command_name === 'end') {
          $token = $ClassName::end($this->tokenizer->getEndEnvTokenData($env));
          $this->tokenizer->addToken($token);
          continue 2;
        }

        $signature = $ClassName::signature();

        // If begin env and env contains optional argument then need to move forward
        // because cursor is at the } character of \begin{env-name}
        if(!is_null($env) && $signature && $signature[0] === '[' && $signature[1] === ']') $this->tokenizer->forward();

        try {
          $token = $ClassName::make($this->tokenizer->getTokenData($signature, $env));
        } catch (\Exception $e) {
          $message = $e->getMessage();
          $message .= "<br>$ClassName";
          $message .= "<br>Line Number: " . $this->tokenizer->getLineNumber();
          $message .= "<br>File: " . __FILE__;
          $message .= "<br>Code line: " . __LINE__;
          throw new \Exception($message);
        }

        $this->tokenizer->addToken($token);

        // Backup if token is a command with no signature and current char is not a blank space
        if ($signature === '' && $this->tokenizer->getChar() !== ' ' && is_null($env)) $this->tokenizer->backup();

        // Some tokens affect how other tokens are generated
        if ($this->isUpdatable($token->command_name)) $this->update($token);

        continue 2;
      }

      if (is_null($env)) {
        $this->tokenizer->addToBuffer("\\" . $this->tokenizer->command_name);
        $this->tokenizer->backup();
        continue;
      }

      $token = $this->tokenizer->command_name === 'end'
        ? $this->default_env::end($this->tokenizer->getEndEnvTokenData($env))
        : $this->default_env::make($this->tokenizer->getTokenData('', $env));

      $this->tokenizer->addToken($token);
    }

    $this->tokenizer->addBufferAsToken();

    $this->postProcessTokens();

    return $this->tokenizer->getTokens();
  }

  public function registerCommandGroup($class_name)
  {
    if (is_array($class_name)) {
      foreach ($class_name as $name) {
        $this->command_groups[] = $name;
      }
      return;
    }

    $this->command_groups[] = $class_name;
  }

  public function registerDefaultEnvironment($class_name)
  {
    $this->default_env = $class_name;
  }

  public function getCommandGroups()
  {
    return $this->command_groups;
  }

  public function getDefaultEnvironment()
  {
    return $this->default_env;
  }

  protected function postProcessTokens(): void
  {
    $this->tokenizer->postProcessTokens();
  }

  protected function update($token) {}

  protected function isUpdatable($command_name) {
    return in_array($command_name, $this->updatable_commands);
  }

}
