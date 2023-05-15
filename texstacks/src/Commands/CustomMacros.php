<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;

class CustomMacros extends CommandGroup {

  protected static $commands = [];
  protected static $newCommandTokens = [];
  protected static $type = 'cmd:custom-macro';

  /**
   * The signature of the command is specified by the associated token
   */
  public static function signature($command_name = null) {

    $token = self::$newCommandTokens[$command_name];

    if (is_null($token->command_args[0])) return '';

    $signature = '';

    $nargs = (int)$token->command_args[0];

    if (!is_null($token->command_args[1])) {
      $nargs -= 1;
      $signature .= '[]';
    }

    $signature .= join('', array_fill(0, $nargs, '{}'));

    return $signature;

  }

  public static function customAdd($newCommandToken) {

    static::add($newCommandToken->body);

    static::$newCommandTokens[$newCommandToken->body] = $newCommandToken;

  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    // Get the associated token for the new command
    $token = self::$newCommandTokens[$args['command_name']];

    list($nargs, $default, $defn) = $token->command_args;

    $args['command_src'] = "\\" . $args['command_name'];

    // If the new command has options, add them to the command source
    if (!is_null($default) && $args['command_options']) {
      $args['command_src'] .= "[" . $args['command_options'] . "]";
    }

    $args['command_src'] .= join('', array_map( fn($arg) => "{" . $arg . "}", $args['command_args'] ));

    if (is_null($nargs)) {
      $args['body'] = $defn;
      return new Token($args);
    }

    // In the new command definition, replace the #1, #2, etc. with the actual arguments
    $hash_nums = array_map( fn($num) => '#' . $num , range(1, $nargs));

    if (is_null($default)) {
      $values = $args['command_args'];
    } else {
      $default = $args['command_options'] ? $args['command_options'] : $default;
      $values = array_merge([$default], $args['command_args']);
    }

    $args['body'] = str_replace($hash_nums, $values, $defn);

    // Rename the arguments to arg1, arg2, etc., possibly used by the Node class
    // $keys = array_map( fn($num) => 'arg' . $num , range(1, $nargs));
    // $args['command_args'] = array_combine($keys, $values);
 
    return new Token($args);
  }

}