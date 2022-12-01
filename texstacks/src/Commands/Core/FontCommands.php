<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\OneArg;

class FontCommands extends OneArg {

  protected static $type = 'cmd:font';

  protected static $commands = [
    'textrm',
    'textsf',
    'texttt',
    'textmd',
    'textbf',
    'textup',
    'textit',
    'textsl',
    'textsc',
    'text',
    'emph',
    'textnormal',
    'textsuperscript',
    'textsubscript',
    'centerline',
  ];
 
}