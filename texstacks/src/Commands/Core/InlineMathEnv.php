<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\Environment;

class InlineMathEnv extends Environment
{

  protected static $type = 'inlinemath';

  protected static $commands = [
    'math',
  ];

  public static function signature()
  {
    return '';
  }

  public static function make($args)
  {
    $args['type'] = self::$type;

    $args['command_src'] = "\\" . $args['command_name'] . "{math}";

    return new Token($args);
  }
}
