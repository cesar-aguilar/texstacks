<?php

namespace TexStacks\Renderers;

class DisplayMathEnvironmentRenderer
{

  public static function renderNode($node, $body = null)
  {

    $body = $body ?? '';
    
    $latex =  $node->commandSource() . $body . "\\end{{$node->commandContent()}}";

    if (($node->ancestorOfType('verbatim'))) return $latex;

    // If $node is a nested displaymath-env, then we need to render it as text
    // if ($node->ancestorOfType('displaymath-environment')) return $latex;

    $div = $node->commandLabel() ? "<div id=\"{$node->commandLabel()}\">" : "<div>";

    return $div . $latex . '</div>';
  }
}
