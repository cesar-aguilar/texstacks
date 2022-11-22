<?php

namespace TexStacks\Commands;

use TexStacks\Commands\EnvWithOptions;

class GenericEnv extends EnvWithOptions
{

  protected static $type = 'environment:generic';

  protected static $commands = [
    'figure',
    'table',
    'proof',
  ];
}
