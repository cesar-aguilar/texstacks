<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;

class TwoArgs extends CommandGroup {

  protected static $type = 'cmd:two_args';
  
  protected static $commands = [
    'texorpdfstring',
  ];

  public static function signature() {
    return '{}{}';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;
    list($arg_1, $arg_2) = $args['command_args'];
    $args['command_src'] = "\\" . $args['command_name'] . "{" . $arg_1 . "}" . "{" . $arg_2 . "}";
    return new Token($args);
  }

}