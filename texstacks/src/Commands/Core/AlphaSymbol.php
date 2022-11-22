<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class AlphaSymbol extends CommandGroup
{

  protected static $type = 'cmd:alpha-symbol';

  protected static $commands = [
    'S',
    'P',
    'pounds',
    'copyright',
    'dag',
    'ddag',
  ];

  public static function make($args)
  {
    $args['type'] = static::$type;

    $args['command_src'] = "\\" . $args['command_name'];

    $args['body'] = $args['command_name'];

    return new Token($args);
  }
}
