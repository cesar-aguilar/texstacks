<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Node;

class LayoutNode extends Node
{

  protected $descendant_name = null;

  const PREFIXES = [
    'document' => 'doc',
    'chapter' => 'ch',
    'section' => 'sec',
    'subsection' => 'ssec',
    'subsubsection' => 'sssec',
  ];

  public function setDescendantName($name)
  {
    $this->descendant_name = $name;
    return $this;
  }

  public function descendantName()
  {
    return $this->descendant_name;
  }

  protected function init()
  {

    // Section commands are special in that they should always have
    // a label to be used in the id attribute for navigation
    if ($this->commandLabel()) return;

    $prefix = $this->command_name ? (self::PREFIXES[$this->command_name] ?? 'text') : 'text';

    $this->command_label =  $prefix . ":node-{$this->id()}";
  }
}
