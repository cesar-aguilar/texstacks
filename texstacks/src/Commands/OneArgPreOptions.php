<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;

class OneArgPreOptions extends CommandGroup {

  protected static $type = 'cmd:options-arg';

  protected static $commands = [
    'includegraphics',
    'caption',
    'bibitem',    
  ];

  public static function signature() {
    return '[]{}';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    $options = $args['command_options'] ? "[" . $args['command_options'] . "]" : '';

    $args['command_src'] = "\\" . $args['command_name'] . $options . "{" . $args['command_content'] . "}";

    $args['body'] = $args['command_content'];

    return new Token($args);
  }

}