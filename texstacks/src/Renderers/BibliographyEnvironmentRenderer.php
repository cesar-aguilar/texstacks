<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class BibliographyEnvironmentRenderer
{
    
    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {
        $body = $body ?? '';

        if ($node->ancestorOfType('verbatim-environment'))
            return $node->commandSource() . $body . "\\end{{$node->commandContent()}}";

        return "<section class=\"section\"><h2>References</h2><ol class=\"references\">$body</ol></section>";

    }
    
}
