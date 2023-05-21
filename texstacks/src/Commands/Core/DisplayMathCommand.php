<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\CommandGroup;
use TexStacks\Parsers\Token;

class DisplayMathCommand extends CommandGroup
{

  protected static $type = 'displaymath';

  public static function signature($command_name = null) {
    return '$$';
  }

  protected static $commands = [
    '[',
    ']',
  ];

  public static function make($args)
  {
    $args['type'] = self::$type;

    $cmd = $args['command_content'] === '[' ? 'begin' : 'end';
    
    $args['command_src'] = "\\" . $cmd . "{equation*}";
    $args['command_name'] = $cmd;
    $args['command_content'] = 'equation*';

    return new Token($args);
  }

}
