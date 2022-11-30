<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\OneArg;

class SpaceCommands extends OneArg {

  protected static $type = 'cmd:space';

  protected static $commands = [    
    'hspace',
    'hspace*',
    'vspace',
    'vspace*',
  ];

}