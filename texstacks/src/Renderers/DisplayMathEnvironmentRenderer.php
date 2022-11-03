<?php

namespace TexStacks\Renderers;

class DisplayMathEnvironmentRenderer
{

  public static function renderNode($node, $body = null)
  {

    $latex =  $node->commandSource() . $body . "\\end{{$node->commandContent()}}";

    if (($node->ancestorOfType('verbatim-environment'))) return $latex;

    // If $node is a nested displaymath-env, then we need to render it as text
    // if ($node->ancestorOfType('displaymath-environment')) return $latex;

    $div = $node->commandLabel() ? "<div id=\"{$node->commandLabel()}\">" : "<div>";

    return $div . $latex . '</div>';
  }
}
