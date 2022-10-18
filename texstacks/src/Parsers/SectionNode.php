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
        return -1;
      case 'chapter':
        return 0;
      case 'section':
        return 1;
      case 'subsection':
        return 2;
      case 'subsubsection':
        return 3;
      default:
        return 4;
    }
  }

  public function setLabel($label=null)
  {

    // Section commands are special in that they should always have
    // a label to be used in the id attribute for navigation
    if ($label) {      
      $this->command_label = $label;
    } else {

      $prefix = $this->command_name ? (self::PREFIXES[$this->command_name] ?? 'text') : 'text';

      $this->command_label =  $prefix . ":node-{$this->id()}";
    }

  }
}
