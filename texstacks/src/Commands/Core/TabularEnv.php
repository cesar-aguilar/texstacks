<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\Environment;

class TabularEnv extends Environment {

  public static $type = 'environment:tabular';

  public static $commands = [
    'tabular',
    'supertabular',
  ];

  public static function signature($command_name = null) {
    return '[]{}';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    $options = $args['command_options'] ? "[" . $args['command_options'] . "]" : '';

    $args['command_src'] = "\\" . $args['command_name'] . "{" . $args['command_content'] . "}" . $options;

    return new Token($args);
  }

}