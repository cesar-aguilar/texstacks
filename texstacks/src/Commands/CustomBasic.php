<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class CustomBasic extends CommandGroup {

  protected static $commands = [];
  protected static $newCommandTokens = [];
  protected static $type = 'cmd:custom-basic';

  use CustomAddTrait;
  
  public static function make($args)
  {
    $args['type'] = static::$type;

    $args['command_src'] = "\\" . $args['command_name'];

    $token = static::$newCommandTokens[$args['command_name']];

    list($params, $default, $defn) = $token->command_args;

    $args['body'] = $defn;
    
    return new Token($args);
  }

}