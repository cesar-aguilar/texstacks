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
    'paragraph' => 'par',
    'subparagraph' => 'subpar',
  ];

  public readonly int $depth_level;

  public function __construct($args)
  {
    parent::__construct($args);
    $this->setLabel();

    $this->depth_level = $this->getDepthLevel();

  }

  public function closestParentSection($node)
  {
    $parent = $node;

    while ($parent::class !== $this::class  || $parent->depth_level >= $this->depth_level)
    {
      $parent = $parent->parent();
    }

    return $parent;

  }

  private function getDepthLevel()
  {
    switch ($this->commandName()) {
      case 'document':
        return -1;
      case 'chapter':
      case 'chapter*':
        return 0;
      case 'section':
      case 'section*':
        return 1;
      case 'subsection':
      case 'subsection*':
        return 2;
      case 'subsubsection':
      case 'subsubsection*':
        return 3;
      case 'paragraph':
      case 'paragraph*':
        return 4;
      case 'subparagraph':
        case 'subparagraph*':
        return 5;
      default:
        return 6;
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

      $this->command_label =  $prefix . ":node-{$this->id}";
    }

  }
}
