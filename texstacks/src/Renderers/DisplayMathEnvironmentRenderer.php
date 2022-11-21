<?php

namespace TexStacks\Renderers;

class DisplayMathEnvironmentRenderer
{

  public static function renderNode($node, $body = null)
  {

    $latex =  $node->commandSource() . $body . "\\end{{$node->commandContent()}}";

    if (($node->ancestorOfType('verbatim-environment'))) return $latex;

    if ($node->commandContent() === 'displaymath') {
      $latex = "\\begin{equation*}" . $body . "\\end{equation*}";
    }

    // If $node is a nested displaymath-env, then we need to render it as text
    // if ($node->ancestorOfType('environment:displaymath')) return $latex;

    $div = $node->commandLabel() ? "<div id=\"{$node->commandLabel()}\">" : "<div>";

    return $div . $latex . '</div>';
  }
}
