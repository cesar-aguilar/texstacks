<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\CommandGroup;
use TexStacks\Parsers\Token;

class InlineMathCommand extends CommandGroup
{

  protected static $type = 'inlinemath';

  public static function signature($command_name = null) {
    return '$';
  }

  protected static $commands = [    
    '(',
    ')',
  ];

  public static function make($args)
  {
    $args['type'] = self::$type;
    $args['command_src'] = "\\" . $args['command_name'];
    $args['command_name'] = $args['command_name'] === '(' ? 'begin' : 'end';
    $args['command_content'] = self::$type;

    return new Token($args);
  }

}
