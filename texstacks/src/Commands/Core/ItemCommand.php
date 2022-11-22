<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\CommandWithOptions;

class ItemCommand extends CommandWithOptions
{

  protected static $type = 'cmd:item';

  protected static $commands = [
    'item',
  ];
}
