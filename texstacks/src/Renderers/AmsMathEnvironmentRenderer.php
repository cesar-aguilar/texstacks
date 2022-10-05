<?php

namespace TexStacks\Renderers;

class AmsMathEnvironmentRenderer
{

  public static function renderNode($node, $body = null)
  {
    $div = $node->commandLabel() ? "<div id=\"{$node->commandLabel()}\">" : "<div>";

    $html = <<<LATEX
      \\begin{{$node->commandContent()}}
      $body
      \\end{{$node->commandContent()}}        
      LATEX;
    return $div . $html . '</div>';
  }
}
