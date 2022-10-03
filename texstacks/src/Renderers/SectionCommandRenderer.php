<?php

namespace TexStacks\Renderers;

use TexStacks\Renderers\Renderer;
use TexStacks\Parsers\Node;

class SectionCommandRenderer extends Renderer
{

  protected function renderNode(Node $node, string $body = null): string
  {
    if (!trim($body)) return '';

    $html = '';

    if ($node->type() === 'layout') {
      $html .= $this->renderLayoutElement($node, $body);
    } else  {
      $html .= "<div>$body</div>";
    }

    return $html;
  }

  private function renderLayoutElement(Node $node, string $body = null): string
  {

    if ($node->commandName() === 'document') {

      return "<main>$body</main>";
    } else if ($node->commandName() === 'chapter') {

      return "<article id=\"{$node->commandLabel()}\" class=\"chapter\"><h1>{$node->commandContent()}</h1>$body</article>";
    } else if ($node->commandName() === 'section') {

      return "<section id=\"{$node->commandLabel()}\" class=\"section\"><h2>{$node->commandContent()}</h2>$body</section>";
    } else if ($node->commandName() === 'subsection') {

      return "<section id=\"{$node->commandLabel()}\" class=\"subsection\"><h3>{$node->commandContent()}</h3>$body</section>";
    } else if ($node->commandName() === 'subsubsection') {

      return "<section id=\"{$node->commandLabel()}\" class=\"subsubsection\"><h4>{$node->commandContent()}</h4>$body</section>";
    } else {
      
      return "<div>$body</div>";
    }
  }
}
