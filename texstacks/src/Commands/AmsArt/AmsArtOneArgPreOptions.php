<?php

namespace TexStacks\Commands\AmsArt;

use TexStacks\Commands\OneArgPreOptions;

class AmsArtOneArgPreOptions extends OneArgPreOptions
{
  public static $commands = [
    'title',
    'author',
    'contrib',
    'subjclass',
  ];
}
