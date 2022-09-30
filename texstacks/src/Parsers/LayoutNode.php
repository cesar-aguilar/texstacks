<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Node;

class LayoutNode extends Node
{

  const PREFIXES = [
    'document' => 'doc',
    'chapter' => 'ch',
    'section' => 'sec',
    'subsection' => 'ssec',
    'subsubsection' => 'sssec',
  ];

  protected function init()
  {
    if ($this->commandLabel()) return;

    $prefix = $this->name ? (self::PREFIXES[$this->name] ?? 'text') : 'text';

    $this->command_label =  $prefix . ":node-{$this->index()}";
  }
}
