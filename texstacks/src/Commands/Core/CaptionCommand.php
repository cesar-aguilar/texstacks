<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\OneArgPreOptions;

class CaptionCommand extends OneArgPreOptions
{

  protected static $type = 'cmd:caption';

  protected static $commands = [
    'caption',
  ];
}
