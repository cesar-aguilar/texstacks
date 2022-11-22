<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\OneArgPreOptions;

class BibItemCommand extends OneArgPreOptions
{

  protected static $type = 'cmd:bibitem';

  protected static $commands = [
    'bibitem',
  ];
}
