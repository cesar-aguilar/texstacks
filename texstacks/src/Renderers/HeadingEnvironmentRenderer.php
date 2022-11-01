<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class HeadingEnvironmentRenderer
{

  public static function renderNode(EnvironmentNode $node, string $body = null): string
  {
    $body = $body ?? '';

    if ($node->ancestorOfType('verbatim-environment')) return "\{$body\}";

    $title = $node->commandRefNum() ? $node->commandRefNum() . '&nbsp;&nbsp;&nbsp;' : '';

    $title .= $body;

    return match ($node->commandOptions()) {

      'chapter', 'chapter*' =>

      "<h1>$title</h1>",

      'section', 'section*' =>

      "<h2>$title</h2>",

      'subsection', 'subsection*' =>

      "<h3>$title</h3>",

      'subsubsection', 'subsubsection*' =>

      "<h4>$title</h4>",

      'paragraph', 'paragraph*', 'subparagraph', 'subparagraph*' =>

      "<strong>$title</strong>",

      default =>

      "<span>$body</span>"
    };
  }
}
