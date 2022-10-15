<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class ThmEnvironmentRenderer
{
    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {
        $heading = ucwords($node->commandContent());
        $heading .= $node->commandOptions() ? ': '. ucwords($node->commandOptions()) : '';

        return "<div class=\"thm-env\"><div class=\"thm-env-head {$node->commandContent()}\" id=\"{$node->commandLabel()}\">$heading</div><div class=\"thm-env-body\">$body</div></div>";
    }

}