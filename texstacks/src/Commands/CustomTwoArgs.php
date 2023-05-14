<?php

namespace TexStacks\Commands;

use TexStacks\Parsers\Token;
use TexStacks\Commands\TwoArgs;

class CustomTwoArgs extends TwoArgs {

  protected static $commands = [];
  protected static $newCommandTokens = [];
  protected static $type = 'cmd:custom-two-args';

  use CustomAddTrait;

  public static function make($args)
  {
    $args['type'] = static::$type;

    list($arg_1, $arg_2) = $args['command_args'];

    $args['command_src'] = "\\" . $args['command_name'] . "{" . $arg_1 . "}" . "{" . $arg_2 . "}";

    $args['command_args'] = ['arg1' => $arg_1, 'arg2' => $arg_2];

    $token = self::$newCommandTokens[$args['command_name']];

    list($params, $default, $defn) = $token->command_args;

    $args['body'] = str_replace(['#1', '#2'], [$arg_1, $arg_2], $defn);

    return new Token($args);
  }

}