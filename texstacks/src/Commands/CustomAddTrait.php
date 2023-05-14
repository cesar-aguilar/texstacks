<?php

namespace TexStacks\Commands;

trait CustomAddTrait  {
  
  protected static $newCommandTokens = [];

  public static function customAdd($commands, $newCommandToken) {
    
    static::add($commands);

    static::$newCommandTokens[$newCommandToken->body] = $newCommandToken;

  }

}