<?php

namespace TexStacks\Commands;

class CommandGroup
{

  protected static $type;
  protected static $commands = [];

  public static function type()
  {
    return static::$type;
  }

  public static function signature($command_name = null)
  {
    return '';
  }

  public static function contains($command)
  {
    return in_array($command, static::$commands);
  }

  public static function add($commands)
  {

    if (is_array($commands)) {
      static::$commands = array_merge(static::$commands, $commands);
    } else {
      static::$commands[] = $commands;
    }
  }

  public static function is_env()
  {
    return str_starts_with(static::$type, 'environment:');
  }

  public static function list()
  {
    return static::$commands;
  }
}
