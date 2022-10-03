<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Node;

abstract class LatexTree
{

  protected array $nodes = [];
  protected Node $root;
  
  public function __construct(protected $root_name = 'document')
  {
  }

  abstract public function build($latex_src);

  abstract protected function addNode($node, $parent = null);

  public function getNodes()
  {
    return $this->nodes;
  }

  public function root()
  {
    return $this->root;
  }
}
