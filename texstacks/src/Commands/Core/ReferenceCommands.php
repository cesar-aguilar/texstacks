<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class ReferenceCommands extends CommandGroup
{

  protected static $type = 'cmd:ref';

  protected static $ref_labels = [];

  protected static $commands = [
    'ref',
    'eqref',
  ];

  public static function signature($command_name = null)
  {
    return '{}';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    $args['command_src'] = "\\" . $args['command_name'] . "{" . $args['command_content'] . "}";

    $args['command_options'] = self::$ref_labels[$args['command_content']] ?? '?';

    return new Token($args);
  }

  /**
   *
   */
  public static function add($ref_labels)
  {
    foreach ($ref_labels as $label => $number) {
      self::$ref_labels[$label] = $number;
    }
  }
}
