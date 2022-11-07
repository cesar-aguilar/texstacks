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

    $float_type = match ($node->parent()?->commandContent()) {
      'figure' => 'Figure',
      'table' => 'Table',
      default => null
    };

    if ($node->commandRefNum()) {
      $body = "$float_type " . $node->commandRefNum() . ': ' . $body;
    } else {
      $body = "$float_type: " . $body;
    }

    return match ($node->parent()?->commandContent()) {

      'figure' => "<figcaption>$body</figcaption>",

      'table' => "<div class=\"table-caption\">$body</div>",

      default => "<div class=\"{$node->parent()?->commandContent()}-caption\">$body</div>",
    };
  }
}
