<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\EnvWithOptions;

class ListEnv extends EnvWithOptions {

  protected static $type = 'environment:list';

  protected static $commands = [
    'itemize',
    'enumerate',
    'compactenum',
    'compactitem',
    'asparaenum',
    'description',
    'algorithmic',
  ];

}