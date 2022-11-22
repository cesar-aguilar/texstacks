<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\OneArgPreOptions;

class IncludeGraphicsCommand extends OneArgPreOptions
{

  protected static $type = 'cmd:includegraphics';

  protected static $commands = [
    'includegraphics',
  ];
}
