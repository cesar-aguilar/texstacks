<?php

namespace TexStacks\Commands\AmsArt;

use TexStacks\Commands\OneArg;

class AmsArtOneArg extends OneArg
{
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
