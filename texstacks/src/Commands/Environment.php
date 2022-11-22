<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;

class Environment extends CommandGroup
{

  protected static $type = 'environment';

  public static function end($args)
  {
    $args['type'] = static::$type;

    $args['command_src'] = "\\" . "end{" . $args['command_content'] . "}";

    return new Token($args);
  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    $args['command_src'] = "\\" . $args['command_name'] . "{" . $args['command_content'] . "}";

    return new Token($args);
  }
}
