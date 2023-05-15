<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class NewTheorem extends CommandGroup {

  protected static $type = 'cmd:newtheorem';

  protected static $commands = [
    'newtheorem',
    'newtheorem*',
  ];

  public static function signature($command_name = null) {
    return '{}[]{}[]';
  }

  public static function make($args)
  {
    $args['type'] = self::$type;

    list($arg1, $options1, $arg2, $options2) = $args['command_args'];

    $args['command_src'] = "\\" . $args['command_name'] . '{' . $arg1 . '}';

    $args['command_src'] .= $options1 ? '[' . $options1 . ']' : '';
    $args['command_src'] .= '{' . $arg2 . '}';
    $args['command_src'] .= $options2 ? '[' . $options2 . ']' : '';

    return new Token($args);
  }

}