<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\OneArg;

class Footnote extends OneArg {

  protected static $type = 'cmd:footnote';

  protected static $commands = [
    'footnote',
  ];

}