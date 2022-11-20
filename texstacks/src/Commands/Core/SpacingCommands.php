<?php

namespace TexStacks\Commands\core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class SpacingCommands extends CommandGroup {

  protected static $type = 'cmd:spacing';

  protected static $commands = [
    'quad',
    'qquad',    
    'smallskip',
    'medskip',
    'bigskip',
    'noindent',
  ];

  public static function make($args)
  {
    $args['type'] = static::$type;

    $args['command_src'] = "\\" . $args['command_name'];

    $args['body'] = $args['command_name'];

    return new Token($args);
  }

}