<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class ListEnvironmentRenderer
{

    const ORDERED_LISTS = [
        'enumerate',
        'compactenum',        
    ];
    
    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {

        if ($node->ancestorOfType('verbatim-environment'))
            return $node->commandSource() . $body . "\\end{{$node->commandContent()}}";

        if (in_array($node->commandContent(), self::ORDERED_LISTS))
        {
            if ($node->commandOptions()) {

                if (trim($node->commandOptions()) === '(a)') return "<ol type=\"a\">$body</ol>";

                if (trim($node->commandOptions()) === '(i)') return "<ol type=\"i\">$body</ol>";
            }

            return "<ol>$body</ol>";
        } else if ($node->commandContent() === 'description') {
            return "<dl>$body</dl>";
        }
        else
        {
            return "<ul>$body</ul>";
        }        
    }
    
}
