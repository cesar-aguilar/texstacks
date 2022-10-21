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
        $body = $body ?? '';

        if ($node->ancestorOfType('verbatim-environment'))
            return $node->commandSource() . $body . "\\end{{$node->commandContent()}}";

        if (in_array($node->commandContent(), self::ORDERED_LISTS))
        {
            return "<ol>$body</ol>";
        } 
        else
        {
            return "<ul>$body</ul>";
        }        
    }
    
}
