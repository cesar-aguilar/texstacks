<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\SectionNode;

class SectionCommandRenderer
{

  public static function renderNode(SectionNode $node, string $body = null): string
  {
    $body = $body ?? '';

    if ($node->ancestorOfType('verbatim')) return $node->commandSource();

    return match ($node->commandName()) {

      'document' => "<main>$body</main>",

      'chapter' => "<article id=\"{$node->commandLabel()}\" class=\"chapter\"><h1>{$node->commandContent()}</h1>$body</article>",

      'section' => "<section id=\"{$node->commandLabel()}\" class=\"section\"><h2>{$node->commandContent()}</h2>$body</section>",

      'subsection' => "<section id=\"{$node->commandLabel()}\" class=\"subsection\"><h3>{$node->commandContent()}</h3>$body</section>",

      'subsubsection' => "<section id=\"{$node->commandLabel()}\" class=\"subsubsection\"><h4>{$node->commandContent()}</h4>$body</section>",

      default => "<div>$body</div>"

    };

  }

}
