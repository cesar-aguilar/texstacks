<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\EnvWithArg;

class BibliographyEnv extends EnvWithArg
{

  public static $type = "environment:bibliography";

  public static $commands = [
    'thebibliography',
  ];
}
