<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\LatexTree;

class EnvironmentTree extends LatexTree
{

  public function build($latex_src)
  {
    $this->latex_src = $latex_src;
    $this->root = new EnvironmentNode(
      index: 0,
      type: 'layout',
      body: $this->latex_src,
      name: $this->root_name,
      child_name: null
    );

    $this->addNode($this->root);
  }

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
  }
}
