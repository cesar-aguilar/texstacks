<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;

class TwoArgs extends CommandGroup
{

  protected static $type = 'cmd:two-args';

  protected static $commands = [
    'texorpdfstring',
    'setcounter',
    'addtocounter',
    'numberwithin',
    // 'setlength',
    // 'settowidth',
    // 'settoheight',
  ];

  public static function signature()
  {
    return '{}{}';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;
    list($arg_1, $arg_2) = $args['command_args'];
    $args['command_src'] = "\\" . $args['command_name'] . "{" . $arg_1 . "}" . "{" . $arg_2 . "}";
    $args['command_args'] = ['arg1' => $arg_1, 'arg2' => $arg_2];
    return new Token($args);
  }
}
