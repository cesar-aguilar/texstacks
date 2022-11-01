<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\SectionNode;

class SectionCommandRenderer
{

  public static function renderNode(SectionNode $node, string $body = null): string
  {
    $body = $body ?? '';

    if ($node->ancestorOfType('verbatim-environment')) return $node->commandSource();

    return match ($node->commandName()) {

      'document' =>

      "<main>$body</main>",

      'chapter', 'chapter*' =>

      "<article id=\"{$node->commandLabel()}\" class=\"chapter\">$body</article>",

      'section', 'section*' =>

      "<section id=\"{$node->commandLabel()}\" class=\"section\">$body</section>",

      'subsection', 'subsection*' =>

      "<section id=\"{$node->commandLabel()}\" class=\"subsection\">$body</section>",

      'subsubsection', 'subsubsection*' =>

      "<section id=\"{$node->commandLabel()}\" class=\"subsubsection\">$body</section>",

      'paragraph', 'paragraph*', 'subparagraph', 'subparagraph*' =>

      "<p id=\"{$node->commandLabel()}\">$body</p>",

      default =>

      "<div>$body</div>"
    };
  }
}
