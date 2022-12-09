<?php

namespace TexStacks\Renderers;

use TexStacks\Renderers\Renderer;
use TexStacks\Nodes\EnvironmentNode;
use TexStacks\Nodes\Node;

class ThmEnvironmentRenderer
{
    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {
        if ($node->ancestorOfType('environment:verbatim')) {
            return $node->commandSource() . $body . "\\end{{$node->commandContent()}}";
        }

        $heading = ucwords($node->getArg('text'));
        $heading .= $node->commandRefNum() ? " {$node->commandRefNum()}" : '';

        if ($node->commandOptions() && $node->commandOptions() instanceof Node) {
            $options = Renderer::render($node->commandOptions());
            $heading .= ' (' . $options . ')';
        }


        $style = $node->getArg('style');

        $body = trim($body);

        return "<div class=\"thm-env thm-style-{$style}\" id=\"{$node->commandLabel()}\"><div class=\"thm-env-head\">$heading</div><div class=\"thm-env-body\">$body</div></div>";
    }
}
