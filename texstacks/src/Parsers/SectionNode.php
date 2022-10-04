<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\CommandNode;

class SectionNode extends CommandNode
{

  const PREFIXES = [
    'document' => 'doc',
    'chapter' => 'ch',
    'section' => 'sec',
    'subsection' => 'ssec',
    'subsubsection' => 'sssec',
  ];

  public function __construct($args)
  {
    parent::__construct($args);
    $this->setLabel();
  }

  public function depthLevel()
  {
    switch ($this->commandName()) {
      case 'document':
        return 0;
      case 'chapter':
        return 1;
      case 'section':
        return 2;
      case 'subsection':
        return 3;
      case 'subsubsection':
        return 4;
      default:
        return 5;
    }
  }

  private function setLabel()
  {

    // Section commands are special in that they should always have
    // a label to be used in the id attribute for navigation
    if ($this->commandLabel()) return;

    $prefix = $this->command_name ? (self::PREFIXES[$this->command_name] ?? 'text') : 'text';

    $this->command_label =  $prefix . ":node-{$this->id()}";
  }
}
