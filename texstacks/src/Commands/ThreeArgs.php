<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class ThreeArgs extends CommandGroup
{

  protected static $type = 'cmd:three-args';

  protected static $commands = [];

  public static function signature()
  {
    return '{}{}{}';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    $args['command_src'] = "\\" . $args['command_name'];

    $args['command_src'] .= join('', array_map( fn($arg) => "{" . $arg . "}", $args['command_args'] ));

    // Rename the arguments to arg1, arg2, etc., possibly used by the Node class
    $keys = array_map( fn($num) => 'arg' . $num , range(1, count($args['command_args'])));
    $args['command_args'] = array_combine($keys, $args['command_args']);

    return new Token($args);
  }
}
