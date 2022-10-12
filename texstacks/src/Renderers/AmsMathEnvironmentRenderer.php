<?php

namespace TexStacks\Renderers;

class AmsMathEnvironmentRenderer
{

  public static function renderNode($node, $body = null)
  {

    if ($node->commandContent() === 'math') {
      return " \( $body \) ";
    }

    $latex =  $node->commandSource() . $body . "\\end{{$node->commandContent()}}";

    // If $node is a nested math-environment, then we need to render it as text
    if ($node->ancestorOfType('math-environment')) {
      return $latex;
    }

    $div = $node->commandLabel() ? "<div id=\"{$node->commandLabel()}\">" : "<div>";

    return $div . $latex . '</div>';
  }
}
