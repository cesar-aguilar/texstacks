<?php

namespace TexStacks\Renderers;

class Renderer
{
  public static function render($root): string
  {
    return self::renderRecursively($root);
  }

  private static function renderRecursively($node): string
  {
    if ($node->isLeaf()) return $node->render($node->body ?? '');

    $body = implode('', array_map(fn ($child) => self::renderRecursively($child), $node->children()));

    return $node->render($body ?? '');
  }
}
