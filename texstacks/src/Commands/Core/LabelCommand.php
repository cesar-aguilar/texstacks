<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\Core\ReferenceCommands;

class LabelCommand extends ReferenceCommands
{

  protected static $type = 'cmd:label';

  protected static $commands = [
    'label',
  ];
}
