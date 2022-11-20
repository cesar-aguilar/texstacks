<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class SpaceCommands extends CommandGroup {

  protected static $type = 'cmd:space';

  protected static $commands = [    
    'hspace',
    'vspace',    
  ];

  public static function signature() {
    return '*{}';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    $args['command_src'] = "\\" . $args['command_name'] . '{' . $args['command_content'] . '}';

    return new Token($args);
  }

}