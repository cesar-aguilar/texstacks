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
    $this->root = new LayoutNode(
      id: 0,
      type: 'layout',
      body: $latex_src,
      command_name: $this->root_name);
      
    $this->root->setDescendantName(self::SECTION_NAMES[$this->root_name]);
      
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
    if ($node->descendantName() === null) {
      return;
    }

    // Recursive case: Split the node body into child nodes
    // usin the first occurring descendant sectioning command
    $descendant_names = $this->getDescendantSectionNames($node->commandName());

    while ($descendant_names) {

      $descendant_name = array_shift($descendant_names);

      $pattern = '/(\\\\' . $descendant_name . '.*)/';

      $lines = preg_split($pattern, $node->body(), flags: PREG_SPLIT_DELIM_CAPTURE);

      if (count($lines) > 1) {
        break;
      }
    }

    // The first element of $lines is the text (could be empty)
    // that comes before the first sectioning command
    $child_body = trim(array_shift($lines));
    $child_node = new LayoutNode(
      id: count($this->nodes),
      type: 'layout',
      body: $child_body
    );

    $node->addChild($child_node);
    $this->addNode($child_node, $node);

    // The rest of $lines contains the sectioning commands and their content
    while ($lines) {
      $latex_command = array_shift($lines);
      $child_body = trim(array_shift($lines));

      $child_node = new LayoutNode(
        id: count($this->nodes),
        type: 'layout',
        body: $child_body,
        command_name: $descendant_name,
        latex_command: $latex_command
      );
      
      $child_node->setDescendantName(self::SECTION_NAMES[$descendant_name]);

      $node->addChild($child_node);
      $this->addNode($child_node, $node);
    }

    $node->setBody('');

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
