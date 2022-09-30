<?php

namespace TexStacks\Renderers;

use TexStacks\Renderers\Renderer;
use TexStacks\Parsers\Node;

class HtmlRenderer extends Renderer
{

  public function renderNode(Node $node, string|null $body = null): string
  {
    $html = '';

    if ($node->type() === 'layout') {
      $html .= self::renderLayoutElement($node, $body);
    } else if ($node->body()) {

      $html .= "<div>{$node->body()}</div>";
    }

    return $html;
  }

  private function renderLayoutElement(Node $node, string $body = null): string
  {
    if ($node->name() === 'document') {

      return "<main>$body</main>";
    } else if ($node->name() === 'chapter') {


      return "<article id=\"{$node->commandLabel()}\" class=\"chapter\"><h1>{$node->commandContent()}</h1>$body</article>";
    } else if ($node->name() === 'section') {

      return "<section id=\"{$node->commandLabel()}\" class=\"section\"><h2>{$node->commandContent()}</h2>$body</section>";
    } else if ($node->name() === 'subsection') {

      return "<section id=\"{$node->commandLabel()}\" class=\"subsection\"><h3>{$node->commandContent()}</h3>$body</section>";
    } else if ($node->name() === 'subsubsection') {

      return "<section id=\"{$node->commandLabel()}\" class=\"subsubsection\"><h4>{$node->commandContent()}</h4>$body</section>";
    } else {

      return "<div>$body</div>";
    }
  }
}
