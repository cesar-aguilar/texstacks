<?php

namespace TexStacks\Nodes;

use TexStacks\Renderers\BibliographyEnvironmentRenderer;
use TexStacks\Nodes\EnvironmentNode;

class BibliographyNode extends EnvironmentNode
{

  public function render($body): string
  {
    return BibliographyEnvironmentRenderer::renderNode($this, $body);
  }

}