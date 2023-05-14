<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;
use TexStacks\Commands\OneArgPreOptions;

class CustomTwoArgWithDefault extends OneArgPreOptions {

  protected static $commands = [];
  protected static $newCommandTokens = [];
  protected static $type = 'cmd:custom-option-arg';

  use CustomAddTrait;

  public static function make($args)
  {
    $args['type'] = static::$type;

    $options = $args['command_options'] ? "[" . $args['command_options'] . "]" : '';

    $args['command_src'] = "\\" . $args['command_name'] . $options . "{" . $args['command_content'] . "}";

    // $args['body'] = $args['command_content'];

    $token = self::$newCommandTokens[$args['command_name']];

    list($params, $default, $defn) = $token->command_args;

    $default = $token->command_options ? $token->command_options : $default;

    $args['body'] = str_replace(['#1', '#2'], [$default, $args['command_content']], $defn);

    return new Token($args);
  }

}