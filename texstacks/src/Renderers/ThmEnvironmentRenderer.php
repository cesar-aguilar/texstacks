<?php

namespace TexStacks\Renderers;

use TexStacks\Renderers\Renderer;
use TexStacks\Parsers\EnvironmentNode;

class ThmEnvironmentRenderer
{
    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {
        if ($node->ancestorOfType('verbatim-environment')) {
            return $node->commandSource() . $body . "\\end{{$node->commandContent()}}";
        }

        $heading = ucwords($node->getArg('text'));
        $heading .= $node->commandRefNum() ? " {$node->commandRefNum()}" : '';

        if ($node->commandOptions()) {
            $options = (new Renderer)->renderTree($node->commandOptions());
            $heading .= ' (' . $options . ')';
        }


        $style = $node->getArg('style');

        return "<div class=\"thm-env thm-style-{$style}\" id=\"{$node->commandLabel()}\"><div class=\"thm-env-head\">$heading</div><div class=\"thm-env-body\">$body</div></div>";
    }

}