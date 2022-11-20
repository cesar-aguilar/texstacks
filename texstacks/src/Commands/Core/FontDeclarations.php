<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class FontDeclarations extends CommandGroup {

  protected static $type = 'cmd:font_declaration';

  protected static $commands = [
    'rm' => 'font-serif',
    'sl' => 'italic',
    'sc' => 'small-caps',
    'it' => 'italic',
    'tt' => 'font-mono',
    'bf' => 'font-bold',
    'bfseries' => 'font-bold',
    'mdseries' => 'font-medium',
    'rmfamily' => 'font-serif',
    'sffamily' => 'italic',
    'ttfamily' => 'font-mono',
    'upshape' => 'non-italic',
    'itshape' => 'italic',
    'scshape' => 'small-caps',
    'slshape' => 'italic',
    'em' => 'italic',
    'normalfont' => 'font-serif',
    'tiny' => 'text-xs',
    'scriptsize' => 'text-xs',
    'footnotesize' => 'text-sm',
    'small' => 'text-sm',
    'normalsize' => 'text-base',
    'large' => 'text-lg',
    'Large' => 'text-xl',
    'LARGE' => 'text-2xl',
    'huge' => 'text-3xl',
    'Huge' => 'text-4xl',
  ];

  public static function contains($command) {
    return array_key_exists($command, self::$commands);
  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    $args['command_src'] = "\\" . $args['command_name'];

    $args['body'] = self::$commands[$args['command_name']];

    return new Token($args);
  }

}