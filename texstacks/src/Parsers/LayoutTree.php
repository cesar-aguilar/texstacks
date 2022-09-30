<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\LayoutNode;
use TexStacks\Parsers\LatexTree;

class LayoutTree extends LatexTree
{

  // LaTeX's hierarchy of sectioning commands
  const SECTION_NAMES = [
    'document' => 'chapter',
    'chapter' => 'section',
    'section' => 'subsection',
    'subsection' => 'subsubsection',
    'subsubsection' => null,
  ];

  public function build($latex_src)
  {
    $this->latex_src = $latex_src;
    $this->root = new LayoutNode(
      index: 0,
      type: 'layout',
      body: $this->latex_src,
      name: $this->root_name,
      child_name: self::SECTION_NAMES[$this->root_name]
    );

    $this->addNode($this->root);
  }

  /**
   * Recursively add nodes to the graph
   */
  protected function addNode($node, $parent = null)
  {
    $this->nodes[] = $node;

    if ($parent) {
      $node->setParent($parent);
    }

    // Base case
    if ($node->isLeaf()) {
      return;
    }

    // Recursive case: Split the node body into child nodes using
    // the first occurring sectioning command of descendant sectioning commands
    $descendant_names = $this->getDescendantSectionNames($node->name());

    while ($descendant_names) {

      $descendant_name = array_shift($descendant_names);

      $pattern = '/(\\\\' . $descendant_name . '.*)/';

      $splitted = preg_split($pattern, $node->body(), flags: PREG_SPLIT_DELIM_CAPTURE);

      if (count($splitted) > 1) {
        break;
      }
    }

    // The first element of $splitted is the text (could be empty)
    // that comes before the first sectioning command
    $child_body = trim(array_shift($splitted));
    $child_node = new LayoutNode(
      index: count($this->nodes),
      type: 'text',
      body: $child_body
    );

    $node->addChild($child_node);
    $this->addNode($child_node, $node);

    // The rest of $splitted contains the sectioning commands and their content
    while ($splitted) {
      $latex_command = array_shift($splitted);
      $child_body = trim(array_shift($splitted));

      $child_node = new LayoutNode(
        index: count($this->nodes),
        type: 'layout',
        body: $child_body,
        name: $descendant_name,
        child_name: self::SECTION_NAMES[$descendant_name],
        latex_command: $latex_command
      );

      $node->addChild($child_node);
      $this->addNode($child_node, $node);
    }
  }

  /**
   * 
   */
  private function getDescendantSectionNames($name)
  {
    if (!$name) return [];

    $current_name = $name;
    $descendant_names = [];

    while ($current_name = self::SECTION_NAMES[$current_name]) {
      $descendant_names[] = $current_name;
    }
    return $descendant_names;
  }
}
