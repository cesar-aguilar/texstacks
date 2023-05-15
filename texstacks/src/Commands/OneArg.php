<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;

class OneArg extends CommandGroup {

  protected static $type = 'cmd:arg';
  
  protected static $commands = [
    'tag',
    'theoremstyle',
  ];

  public static function signature($command_name = null) {
    return '{}';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;
    $args['command_src'] = "\\" . $args['command_name'] . "{" . $args['command_content'] . "}";
    return new Token($args);
  }

}