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

  public static function signature()
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
    return str_contains(static::$type, 'environment');
  }

  public static function list()
  {
    return static::$commands;
  }
}
