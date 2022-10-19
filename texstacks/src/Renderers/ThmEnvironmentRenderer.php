<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class ThmEnvironmentRenderer
{
    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {
        if ($node->ancestorOfType('verbatim')) {
            return $node->commandSource() . $body . "\\end{{$node->commandContent()}}";
        }
        
        $heading = ucwords($node->commandContent());
        $heading .= $node->commandRefNum() ? " {$node->commandRefNum()}" : '';
        $heading .= $node->commandOptions() ? ' (' . $node->commandOptions() . ')' : '';

        return "<div class=\"thm-env\"><div class=\"thm-env-head {$node->commandContent()}\" id=\"{$node->commandLabel()}\">$heading</div><div class=\"thm-env-body\">$body</div></div>";
    }

}