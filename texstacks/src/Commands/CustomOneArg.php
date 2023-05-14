<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;
use TexStacks\Commands\OneArg;

class CustomOneArg extends OneArg {

  protected static $commands = [];
  protected static $newCommandTokens = [];
  protected static $type = 'cmd:custom-arg';

  use CustomAddTrait;

  public static function make($args)
  {
    $args['type'] = static::$type;
    
    $args['command_src'] = "\\" . $args['command_name'] . "{" . $args['command_content'] . "}";

    $token = self::$newCommandTokens[$args['command_name']];

    list($params, $default, $defn) = $token->command_args;

    $args['body'] = str_replace('#1', $args['command_content'], $defn);

    return new Token($args);
  }

}