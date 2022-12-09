<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\EnvironmentNode;

class DisplayMathNode extends EnvironmentNode
{

  public function render($body): string
  {

    $latex =  $this->commandSource() . $body . "\\end{{$this->commandContent()}}";

    if (($this->ancestorOfType('environment:verbatim'))) return $latex;

    if ($this->commandContent() === 'displaymath') {
      $latex = "\\begin{equation*}" . $body . "\\end{equation*}";
    }

    // If $this is a nested displaymath-env, then we need to render it as text
    // if ($this->ancestorOfType('environment:displaymath')) return $latex;

    $div = $this->commandLabel() ? "<div id=\"{$this->commandLabel()}\">" : "<div>";

    return $div . $latex . '</div>';
  }

}