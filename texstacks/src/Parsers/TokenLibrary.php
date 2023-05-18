<?php

namespace TexStacks\Parsers;

abstract class TokenLibrary {

  protected $command_groups = [];
  protected $default_env;
  protected $updatable_commands = [];

  /**
   * 
   */
  public function defaultEnv()
  {
    return $this->default_env::type();
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
  public function getCommandGroup($commandName, $envName) {

    $commandGroup = null;
    $is_env = $commandName === 'begin' || $commandName === 'end';

    if($is_env) {

      $queryName = $envName ?? '';
      // Will ensure that we only check for environment groups
      $lambda = fn($x) => $x;

    } else {

      $queryName = $commandName;
      // Will ensure that we only check for non-environment groups, i.e, command groups
      $lambda = fn($x) => !$x;

    }

    foreach ($this->getCommandGroups() as $registeredCommandGroup) {

      if($lambda($registeredCommandGroup::is_env()) && $registeredCommandGroup::contains($queryName)) {
        $commandGroup = $registeredCommandGroup;
        break;
      }

    }

    return $commandGroup;

  }



  /**
   * 
   */
  private function getCommandGroups()
  {
    return $this->command_groups;
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