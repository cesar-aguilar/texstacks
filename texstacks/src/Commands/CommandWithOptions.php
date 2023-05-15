<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;

class CommandWithOptions extends CommandGroup
{

  protected static $type = 'cmd:options';

  public static function signature($command_name = null)
  {
    return '[]';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    $options = $args['command_options'] ? "[" . $args['command_options'] . "]" : '';

    $args['command_src'] = "\\" . $args['command_name'] . $options;

    return new Token($args);
  }
}
