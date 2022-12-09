<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\Environment;

class DisplayMathEnv extends Environment {

  protected static $type = 'environment:displaymath';

  protected static $commands = [
    'equation',
    'equation*',
    'align',
    'align*',
    'multline',
    'multline*',
    'gather',
    'gather*',
    'flalign',
    'flalign*',
    'eqnarray',
    'eqnarray*',
    'displaymath',
  ];
  
}