<?php

namespace TexStacks\Renderers;

use TexStacks\Nodes\EnvironmentNode;

class GroupEnvironmentRenderer
{

    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {

        if ($node->ancestorOfType(['environment:displaymath', 'environment:inlinemath', 'environment:inlinemath', 'inlinemath', 'displaymath', 'environment:verbatim'])) {
            return "{" . $body . "}";
        }

        $html = "<span";

        if ($node->hasClasses()) {
            $html .= " class=\"{$node->getClasses()}\"";
        }

        $html .= ">$body</span>";

        return $html;
    }
}
