<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class NewCommands extends CommandGroup {

  protected static $type = 'cmd:newcommand';

  protected static $commands = [
    'newcommand',
    'renewcommand',
    'providecommand',
  ];

  public static function signature() {
    return '{}[][]{}';
  }

  public static function make($args)
  {
    $args['type'] = self::$type;

    $args['command_src'] = "\\" . $args['command_name'] . '{' . $args['command_content'] . '}';
    list($params, $default_param, $defn) = $args['command_args'];

    $args['command_src'] .= !is_null($params) ? '[' . $params . ']' : '';
    $args['command_src'] .= !is_null($default_param) ? '[' . $default_param . ']' : '';
    $args['command_src'] .= '{' . $defn . '}';

    return new Token($args);
  }

}