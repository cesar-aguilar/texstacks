<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;

class EnvWithArg extends Environment {

  public static $type = "environment:arg";

  public static $commands = [
    'thebibliography',
  ];

  public static function signature() {
    return '+{}';
  }
 
  public static function make($args)
  {
    $args['type'] = static::$type;
    
    $args['command_src'] = "\\" . $args['command_name'] . "{" . $args['command_content'] . "}" ;

    $the_arg = $args['command_args'][0] ?? '?';

    $args['command_src'] .= "{" . $the_arg . "}";

    return new Token($args);
  }

}