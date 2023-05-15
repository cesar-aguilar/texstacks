<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class AccentCommand extends CommandGroup {

  const VALID_LETTERS = ['a', 'e', 'i', 'o', 'u', 'y', 'n', 'A', 'E', 'I', 'O', 'U', 'Y', 'N'];

  const ACCENT_CMDS = [
    "'" => 'acute',
    "`" => 'grave',
    "^" => 'circ',
    '"' => 'uml',
    '~' => 'tilde',
  ];

  protected static $type = 'cmd:accent';

  protected static $commands = [
    "'",
    "`",
    "^",
    '"',
    '~',
  ];

  public static function signature($command_name = null)
  {
    return '^';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    $letter = $args['command_content'];
    $char = $args['command_name'];

    $args['command_src'] = "\\" . $char . $letter;

    if (!in_array($letter, self::VALID_LETTERS)) {
      $args['body'] = '?';
    } else {
      $accent = self::ACCENT_CMDS[$char];

      $args['body'] = "&$letter$accent;";
    }

    return new Token($args);
  }

}