<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class BibliographyEnvironmentRenderer
{

    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {

        if ($node->ancestorOfType('environment:verbatim'))
            return $node->commandSource() . $body . "\\end{{$node->commandContent()}}";

        return "<section class=\"section\"><h2>References</h2><ol class=\"references\">$body</ol></section>";
    }
}
