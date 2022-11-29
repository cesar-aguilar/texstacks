<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Tokenizer;

class BaseLexer extends Tokenizer
{

  private $command_groups = [];
  private $default_env;

  public function __construct($data = [])
  {
    $this->line_number = $data['line_number_offset'] ?? 1;

    $this->default_env = \TexStacks\Commands\Environment::class;
  }

  public function tokenize(string $latex_src)
  {

    $this->stream = $this->preProcessLatexSource($latex_src);

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
      $env = null;

      if ($this->command_name === 'begin' || $this->command_name === 'end') {
        try {
          $env = $this->getEnvName();
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage() . "<br>Code line: " . __LINE__);
        }
      }

      foreach ($this->command_groups as $ClassName) {

        if (!$ClassName::contains($env ?? $this->command_name)) continue;

        if ($ClassName::is_env() && is_null($env)) continue;

        if (!is_null($env) && $this->command_name === 'end') {
          $token = $ClassName::end($this->getEndEnvTokenData($env));
          $this->addToken($token);
          continue 2;
        }

        $signature = $ClassName::signature();

        try {
          $token = $ClassName::make($this->getTokenData($signature, $env));
        } catch (\Exception $e) {
          $message = $e->getMessage();
          $message .= "<br>$ClassName";
          $message .= "<br>Line Number: " . $this->line_number;
          $message .= "<br>Code line: " . __LINE__;
          throw new \Exception($message);
        }

        $this->addToken($token);

        if ($signature === '' && $this->getChar() !== ' ' && is_null($env)) {
          $this->backup();
        }

        continue 2;
      }

      if (is_null($env)) {
        $this->buffer .= "\\" . $this->command_name;
        $this->backup();
        continue;
      }

      $token = $this->command_name === 'end'
        ? $this->default_env::end($this->getEndEnvTokenData($env))
        : $this->default_env::make($this->getTokenData('', $env));

      $this->addToken($token);
    }

    $this->addBufferAsToken();

    $this->postProcessTokens();

    return $this->tokens;
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

    foreach ($this->tokens as $k => $token) {

      if ($token->type !== 'text') continue;

      if ($k === count($this->tokens) - 1) continue;

      if ($k === 0) {
        $this->tokens[$k]->body = rtrim($token->body, "\n");
        continue;
      }

      if (str_contains($this->tokens[$k + 1]->type, 'environment') || $this->tokens[$k + 1]->type == 'cmd:section') {
        $this->tokens[$k]->body = rtrim($token->body);
        // $this->tokens[$k]->body = preg_replace('/(\n[\s\t]*){2,}/', '', $token->body);
      }

      if (str_contains($this->tokens[$k - 1]->type, 'environment') || $this->tokens[$k - 1]->type == 'cmd:section') {
        $this->tokens[$k]->body = ltrim($token->body);
        // $this->tokens[$k]->body = preg_replace('/(\n[\s\t]*){2,}/', '', $token->body);
      }
    }
  }

  protected function preProcessLatexSource(string $latex_src) {
    return $latex_src;
  }

}
