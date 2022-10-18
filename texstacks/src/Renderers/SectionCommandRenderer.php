<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\SectionNode;

class SectionCommandRenderer
{

  public static function renderNode(SectionNode $node, string $body = null): string
  {
    $body = $body ?? '';

    if ($node->ancestorOfType('verbatim')) return $node->commandSource();

    $title = $node->commandRefNum() ? $node->commandRefNum() . '&nbsp;&nbsp;&nbsp;' : '';

    $title .= $node->commandContent();

    return match ($node->commandName())
    {

      'document' => "<main>$body</main>",

      'chapter', 'chapter*' => "<article id=\"{$node->commandLabel()}\" class=\"chapter\"><h1>$title</h1>$body</article>",

      'section', 'section*' => "<section id=\"{$node->commandLabel()}\" class=\"section\"><h2>$title</h2>$body</section>",

      'subsection', 'subsection*' => "<section id=\"{$node->commandLabel()}\" class=\"subsection\"><h3>$title</h3>$body</section>",

      'subsubsection', 'subsubsection*' => "<section id=\"{$node->commandLabel()}\" class=\"subsubsection\"><h4>$title</h4>$body</section>",

      default => "<div>$body</div>"

    };

  }

}
