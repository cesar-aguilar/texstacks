<?php

namespace TexStacks\Commands\Core;

use TexStacks\Commands\OneArgPreOptions;

class SectionCommands extends OneArgPreOptions {

  protected static $type = 'cmd:section';

  protected static $commands = [
    'part',
    'chapter', 'chapter*',
    'section', 'section*',
    'subsection', 'subsection*',
    'subsubsection', 'subsubsection*',
    'paragraph', 'paragraph*',
    'subparagraph', 'subparagraph*',
  ];

}