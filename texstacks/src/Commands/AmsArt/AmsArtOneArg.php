<?php

namespace TexStacks\Commands\AmsArt;

use TexStacks\Commands\OneArg;

class AmsArtOneArg extends OneArg
{

  protected static $type = 'cmd:frontmatter';

  public static $commands = [
    'address',
    'curraddr',
    'email',
    'urladdr',
    'dedicatory',
    'date',
    'thanks',
    'translator',
    'keywords',
  ];
}
