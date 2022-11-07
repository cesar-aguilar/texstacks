<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\Node;
use TexStacks\Parsers\CommandNode;

class CaptionRenderer
{

  public static function renderNode(CommandNode $node, string $body = null): string
  {
    if ($node->ancestorOfType('verbatim-environment')) return $node->commandSource();

    if ($node->commandContent() instanceof Node) {
      $body = Renderer::render($node->commandContent());
    }

    if ($node->commandRefNum()) {
      $body = "Figure " . $node->commandRefNum() . ': ' . $body;
    } else {
      $body = "Figure: " . $body;
    }

    return match ($node->parent()?->commandContent()) {

      'figure' => "<figcaption>$body</figcaption>",

      'table' => "<div class=\"table-caption\">$body</div>",

      default => "<div class=\"{$node->parent()?->commandContent()}-caption\">$body</div>",
    };
  }
}
