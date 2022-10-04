<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Node;

class SyntaxTree
{

  protected array $nodes = [];
  protected Node $root;
  private int $node_count = 0;

  public function addNode($node, $parent = null)
  {
    $this->nodes[] = $node;
    $this->node_count++;

    if ($parent) {
      $node->setParent($parent);
      $parent->addChild($node);
    }
  }

  public function getNodes()
  {
    return $this->nodes;
  }

  public function nodeCount()
  {
    return $this->node_count;
  }

  public function setRoot($root)
  {
    $this->nodes[] = $root;
    $this->root = $root;

    $this->node_count++;
  }

  public function root()
  {
    return $this->root;
  }
}
