<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\Environment;

class VerbatimEnv extends Environment
{

  protected static $type = 'environment:verbatim';

  protected static $commands = [
    'verbatim',
  ];
}
