<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;

class EnvWithOptions extends Environment
{

  public static function signature($command_name = null)
  {
    return '[]';
  }

  protected static $commands = [];

  public static function make($args)
  {
    $args['type'] = static::$type;

    $options = $args['command_options'] ? "[" . $args['command_options'] . "]" : '';

    $args['command_src'] = "\\" . $args['command_name'] . "{" . $args['command_content'] . "}" . $options;

    return new Token($args);
  }
}
