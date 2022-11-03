<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class GroupEnvironmentRenderer
{
 
    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {

        if ($node->ancestorOfType(['displaymath-environment', 'inlinemath', 'verbatim-environment'])) {
            return " {" . $body . "} ";
        }

        $html = "<span";

        if ($node->hasClasses()) {
            $html .= " class=\"{$node->getClasses()}\"";
        }

        $html .= ">$body</span>";

        return $html;

    }

}