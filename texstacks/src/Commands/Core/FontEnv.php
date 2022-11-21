<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\Environment;

class FontEnv extends Environment {

  protected static $type = 'environment:font';

  protected static $commands = [
    'rmfamily',
    'sffamily',
    'ttfamily',
    'mdseries',
    'bfseries',
    'upshape',
    'itshape',
    'slshape',
    'scshape',
    'em',
    'normalfont',
    'tiny',
    'scriptsize',
    'footnotesize',
    'small',
    'normalsize',
    'large',
    'Large',
    'LARGE',
    'huge',
    'Huge',    
  ];

  public static function make($args)
  {
    $args['type'] = self::$type;

    $args['command_src'] = "\\" . $args['command_name'] . "{" . $args['command_content'] . "}";

    return new Token($args);
  }
  
}