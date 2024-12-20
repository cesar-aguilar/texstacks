<?php

namespace TexStacks\Renderers;

use TexStacks\Nodes\Node;
use TexStacks\Renderers\Renderer;
use TexStacks\Nodes\SectionNode;

class SectionRenderer
{

  public static function renderNode(SectionNode $node, string $body = null): string
  {

    $title = $node->commandRefNum() ? $node->commandRefNum() . '&nbsp;&nbsp;&nbsp;' : '';

    if ($node->commandContent() instanceof Node) {
      $section_name = Renderer::render($node->commandContent());
      $title .= " $section_name";
    } else {
      $title .= $node->commandContent();
    }

    if ($node->ancestorOfType('environment:verbatim')) return $node->commandSource();

    $body = trim(preg_replace('/^(<br>)+|(<br>)+$/', '', $body));

    return match ($node->commandName()) {

      'chapter', 'chapter*' =>

      "<article id=\"{$node->commandLabel()}\" class=\"chapter\"><h1>$title</h1>$body</article>",

      'section', 'section*' =>

      "<section id=\"{$node->commandLabel()}\" class=\"section\"><h2>$title</h2>$body</section>",

      'subsection', 'subsection*' =>

      "<section id=\"{$node->commandLabel()}\" class=\"subsection\"><h3>$title</h3>$body</section>",

      'subsubsection', 'subsubsection*' =>

      "<section id=\"{$node->commandLabel()}\" class=\"subsubsection\"><h4>$title</h4>$body</section>",

      'paragraph', 'paragraph*', 'subparagraph', 'subparagraph*' =>

      "<p id=\"{$node->commandLabel()}\"><strong>$title</strong> $body</p>",

      default => $body
    };
  }
}
