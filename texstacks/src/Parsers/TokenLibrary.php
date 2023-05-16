<?php

namespace TexStacks\Parsers;

abstract class TokenLibrary {

  protected $command_groups = [];
  protected $default_env;
  protected $updatable_commands = [];

  /**
   * 
   */
  public function getCommandGroups()
  {
    return $this->command_groups;
  }

  /**
   * 
   */
  public function defaultEnv()
  {
    return $this->default_env;
  }

  /**
   * 
   */
  public function isUpdatable($command_name) {
    return in_array($command_name, $this->updatable_commands);
  }

  /**
   * 
   */
  abstract public function update($token): void;

  /**
   * 
   */
  protected function addUpdatableCommand($command)
  {
    if (is_array($command)) {
      $this->updatable_commands = array_merge($this->updatable_commands, $command);
    } else {
      $this->updatable_commands[] = $command;
    }
  }

  /**
   * 
   */
  // private function registerCommandGroup($class_name)
  // {
  //   if (is_array($class_name)) {
  //     foreach ($class_name as $name) {
  //       $this->command_groups[] = $name;
  //     }
  //     return;
  //   }

  //   $this->command_groups[] = $class_name;
  // }

}