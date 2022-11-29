<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\OneArgPreOptions;

class PreambleCommands extends OneArgPreOptions
{

  protected static $type = 'cmd:preamble';

  protected static $commands = [
    'documentclass',
    'usepackage',    
  ];
}
