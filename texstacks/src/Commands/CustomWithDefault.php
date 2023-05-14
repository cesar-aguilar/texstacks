<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandWithOptions;

class CustomWithDefault extends CommandWithOptions {

  protected static $commands = [];
  protected static $newCommandTokens = [];
  protected static $type = 'cmd:custom-option';

  use CustomAddTrait;

  public static function make($args)
  {
    $args['type'] = static::$type;

    $options = $args['command_options'] ? "[" . $args['command_options'] . "]" : '';

    $args['command_src'] = "\\" . $args['command_name'] . $options;

    $token = self::$newCommandTokens[$args['command_name']];

    list($params, $default, $defn) = $token->command_args;

    $default = $token->command_options ? $token->command_options : $default;

    $args['body'] = str_replace('#1', $default, $defn);

    return new Token($args);
  }

}