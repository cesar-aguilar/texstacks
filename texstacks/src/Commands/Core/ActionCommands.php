<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class ActionCommands extends CommandGroup {

  protected static $type = 'cmd:action';

  protected static $commands = [
    'appendix',
    'maketitle',
    'tableofcontents',
    'listoffigures',
    'listoftables',
    'newpage',
    'clearpage',
  ];

  public static function make($args)
  {
    $args['type'] = static::$type;

    $args['command_src'] = "\\" . $args['command_name'];

    return new Token($args);
  }

}