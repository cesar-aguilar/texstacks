<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\Node;
use TexStacks\Renderers\Renderer;
use TexStacks\Nodes\EnvironmentNode;

class TheoremNode extends EnvironmentNode
{

  public function render($body = null)
  {

    $body = $body ?? '';

    if ($this->ancestorOfType('environment:verbatim')) {
      return $this->commandSource() . $body . "\\end{{$this->commandContent()}}";
    }

    $heading = ucwords($this->getArg('text'));
    $heading .= $this->commandRefNum() ? " {$this->commandRefNum()}" : '';

    if ($this->commandOptions() && $this->commandOptions() instanceof Node) {
        $options = Renderer::render($this->commandOptions());
        $heading .= ' (' . $options . ')';
    }


    $style = $this->getArg('style');

    $body = trim($body);

    return "<div class=\"thm-env thm-style-{$style}\" id=\"{$this->commandLabel()}\"><div class=\"thm-env-head\">$heading</div><div class=\"thm-env-body\">$body</div></div>";
  }

}