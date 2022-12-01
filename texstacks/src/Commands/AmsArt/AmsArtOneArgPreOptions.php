<?php

namespace TexStacks\Commands\AmsArt;

use TexStacks\Commands\OneArgPreOptions;

class AmsArtOneArgPreOptions extends OneArgPreOptions
{

  protected static $type = 'cmd:frontmatter';

  public static $commands = [
    'title',
    'author',
    'contrib',
    'subjclass',
  ];
}
