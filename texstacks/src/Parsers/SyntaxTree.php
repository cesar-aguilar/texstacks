<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Node;

class SyntaxTree
{

  protected array $nodes = [];
  protected Node $root;
  protected Node $document;
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

  public function prependNode($node)
  {
    if ($this->document) {
      $this->document->prependChild($node);
    } else {
      $this->root->prependChild($node);
    }

  }

  public function getNodes()
  {
    return $this->nodes;
  }

  public function getLastNode() {
    return $this->node_count ? $this->nodes[$this->node_count - 1] : null;
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

  public function setDocument($document)
  {
    $this->document = $document;
  }

  public function root()
  {
    return $this->root;
  }

  public function document()
  {
    return $this->document ?? $this->root;
  }
}
