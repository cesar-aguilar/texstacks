<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CustomAddTrait;

class CustomThreeArgs extends CommandGroup {

  protected static $commands = [];
  protected static $newCommandTokens = [];
  protected static $type = 'cmd:custom-three-args';

  public static function signature()
  {
    return '{}{}{}';
  }

  use CustomAddTrait;

  public static function make($args)
  {
    $args['type'] = static::$type;

    $args['command_src'] = "\\" . $args['command_name'];

    $args['command_src'] .= join('', array_map( fn($arg) => "{" . $arg . "}", $args['command_args'] ));

    // Get the associated token for the new command
    $token = self::$newCommandTokens[$args['command_name']];
    list($nargs, $default, $defn) = $token->command_args;

    // In the new command definition, replace the #1, #2, etc. with the actual arguments
    $hash_nums = array_map( fn($num) => '#' . $num , range(1, $nargs));
    $args['body'] = str_replace($hash_nums, $args['command_args'], $defn);

    // Rename the arguments to arg1, arg2, etc., possibly used by the Node class
    $keys = array_map( fn($num) => 'arg' . $num , range(1, $nargs));
    $args['command_args'] = array_combine($keys, $args['command_args']);

    return new Token($args);
  }
 
}