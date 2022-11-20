<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class FontCommands extends CommandGroup {

  protected static $type = 'cmd:font';

  protected static $commands = [
    'textrm',
    'textsf',
    'texttt',
    'textmd',
    'textbf',
    'textup',
    'textit',
    'textsl',
    'textsc',
    'text',
    'emph',
    'textnormal',
    'textsuperscript',
    'textsubscript',
    'footnote',
  ];
 
  public static function signature() {
    return '{}';
  }

  public static function make($args)
  {
    $args['type'] = self::$type;

    $args['command_src'] = "\\" . $args['command_name'] . "{" . $args['command_content'] . "}";

    return new Token($args);
  }

}