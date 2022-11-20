<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\CommandGroup;

class Citations extends CommandGroup {

  protected static $type = 'cmd:cite';

  protected static $citations = [];

  protected static $commands = [
    'cite',
  ];

  public static function signature() {
    return '[]{}';
  }

  public static function make($args)
  {
    $args['type'] = static::$type;

    $options = $args['command_options'] ? "[" . $args['command_options'] . "]" : '';

    $args['command_src'] = "\\" . $args['command_name'] . $options . "{" . $args['command_content'] . "}";
        
    $args['body'] = self::makeCitationBody($args['command_content']);
    
    return new Token($args);
  }

  /**
   *
   */
  private static function makeCitationBody($command_content) {

    $labels = array_map(trim(...), explode(',', $command_content));

    $citation_numbers = [];

    foreach ($labels as $label) {
      if (isset(self::$citations[$label])) {
        $citation_numbers[] = self::$citations[$label];
      } else {
        $citation_numbers[] = '?';
      }
    }

    return implode(',', $citation_numbers);
  }

  /**
   *
   */
  public static function add($citations) {
    foreach ($citations as $label => $number) {
      self::$citations[$label] = $number;
    }
  }

}