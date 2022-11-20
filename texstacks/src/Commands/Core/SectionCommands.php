<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class SectionCommands extends CommandGroup {

  protected static $type = 'cmd:section';

  protected static $commands = [
    'part',
    'chapter', 'chapter\*',
    'section', 'section\*',
    'subsection', 'subsection\*',
    'subsubsection', 'subsubsection\*',
    'paragraph', 'paragraph\*',
    'subparagraph', 'subparagraph\*',
  ];
  
  public static function signature() {
    return '*[]{}';
  }

  public static function make($args)
  {
    $args['type'] = self::$type;
    
    $options = $args['command_options'] ? "[" . $args['command_options'] . "]" : '';
    
    $args['command_src'] = "\\" . $options . $args['command_name'] . "{" . $args['command_content'] . "}";
    
    return new Token($args);
  }

}