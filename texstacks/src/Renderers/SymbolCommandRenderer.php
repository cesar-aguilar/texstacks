<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\Node;

class SymbolCommandRenderer
{
    public static function renderNode(Node $node, string $body = null): string
    {
        if ($node->ancestorOfType(['displaymath-environment', 'verbatim'])) 
            return "\\" . $node->body;

        return match($node->body)
        {
            '$' => '&#36;',
            '%' => '%',
            '&' => '&',
            '#' => '#',
            '_' => '_',
            '{' => '{',
            '}' => '}',
            "\\" => '\\',
            default => "\\" . $node->body
        };
    }
}