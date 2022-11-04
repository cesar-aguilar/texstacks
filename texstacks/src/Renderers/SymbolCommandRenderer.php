<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\Node;

class SymbolCommandRenderer
{
    public static function renderNode(Node $node, string $body = null): string
    {
        if ($node->ancestorOfType(['displaymath-environment', 'verbatim-environment', 'inlinemath'])) {
            if ($node instanceof Node) return "\\" . $node->body;
            return $node->commandSource();
        }


        return match ($node->body) {
            '$' => '&#36;',
            '%' => '%',
            '&' => '&',
            '#' => '#',
            '_' => '_',
            '-' => '',
            '{' => '{',
            '}' => '}',
            "\\" => '<br>',
            ' ' => '&nbsp;',
            ',' => ' ',
            'S' => '&sect;',
            'P' => '&para;',
            'pounds' => '&pound;',
            'dag' => '&dagger;',
            'ddag' => '&Dagger;',
            'copyright' => '&copy;',
            default => "\\" . $node->body
        };
    }
}
