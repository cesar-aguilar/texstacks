<?php

namespace TexStacks\Commands\Core;

use TexStacks\Parsers\Token;
use TexStacks\Commands\Environment;

class InlineMathEnv extends Environment
{

  protected static $type = 'environment:inlinemath';

  protected static $commands = [
    'math',
  ];

}
