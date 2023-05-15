<?php

namespace TexStacks\Commands\core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandWithOptions;

class SymbolCommand extends CommandWithOptions {

  protected static $type = 'cmd:symbol';

  protected static $commands = [
    '$',
    '%',
    '&',
    '#',
    '_',
    '-',
    '{',
    '}',
    "\\",
    "/",
    ' ',
    ',',
  ];

  public static function signature($command_name = null) {
    return '+[]';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    $options = $args['command_options'] ? "[" . $args['command_options'] . "]" : '';

    $args['command_src'] = "\\" . $args['command_name'] . $options;

    $args['body'] = $args['command_name'];

    return new Token($args);
  }

}