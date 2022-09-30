<?php

namespace TexStacks;

use TexStacks\LayoutGraphNode;

class LayoutGraph
{

  // LaTeX's hierarchy of sectioning commands
  const CHILD_MAP = [
    'document' => 'chapter',
    'chapter' => 'section',
    'section' => 'subsection',
    'subsection' => 'subsubsection',
    'subsubsection' => null,
  ];

  private $nodes = [];
  private $root;

  /**
   *
   */
  public function __construct(private $latex_src, private $root_type = 'document')
  {

    $this->root = new LayoutGraphNode(
      type: $this->root_type,
      body: $this->latex_src,
      child_type: self::CHILD_MAP[$this->root_type]
    );

    $this->addNode($this->root);
  }

  public function getNodes()
  {
    return $this->nodes;
  }

  public function root()
  {
    return $this->root;
  }

  /**
   * Recursively add nodes to the graph
   */
  private function addNode($node, $parent = null)
  {
    $this->nodes[] = $node;

    if ($parent) {
      $node->setParent($parent);
    }

    // Base case: node is a leaf
    if ($node->isLeaf()) {
      return;
    }

    // Recursive case: node has children which start with latex command 
    // \child_type or with a descendant of \descendant_type
    $descendant_types = $this->getDescendantTypes($node->childType());

    while ($descendant_types) {

      $descendant_type = array_shift($descendant_types);

      $pattern = '/(\\\\' . $descendant_type . '.*)/';

      $splitted = preg_split($pattern, $node->body(), flags: PREG_SPLIT_DELIM_CAPTURE);

      if (count($splitted) > 1) {
        break;
      }
    }

    // The first element of $splitted is the text (could be empty)
    // that comes before the first sectioning command
    $child_body = array_shift($splitted);
    $child_node = new LayoutGraphNode(type: 'text', body: $child_body);
    $node->addChild($child_node);
    $this->addNode($child_node, $node);

    // The rest of the array contains the sectioning commands and their content
    while ($splitted) {
      $command = array_shift($splitted);
      $child_body = array_shift($splitted);

      $child_node = new LayoutGraphNode(
        type: $descendant_type,
        body: $child_body,
        child_type: self::CHILD_MAP[$descendant_type],
        command: $command
      );

      $node->addChild($child_node);
      $this->addNode($child_node, $node);
    }
  }

  /**
   * 
   */
  private function getDescendantTypes($type)
  {
    $descendant_types = [];
    $current_type = $type;
    while ($current_type) {
      $descendant_types[] = $current_type;
      $current_type = self::CHILD_MAP[$current_type];
    }
    return $descendant_types;
  }
}
