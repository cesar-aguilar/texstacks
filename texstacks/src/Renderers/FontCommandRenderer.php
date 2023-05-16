<?php

namespace TexStacks\Renderers;

use TexStacks\Nodes\Node;
use TexStacks\Nodes\CommandNode;

class FontCommandRenderer
{

    public static function renderNode(CommandNode $node, string $body = null): string
    {

        if ($node->ancestorOfType(['environment:displaymath', 'inlinemath', 'environment:verbatim'])) return $node->commandSource();

        $body = $node->commandContent();

        if ($node->commandContent() instanceof Node) {
            $body = Renderer::render($node->commandContent());
        }

        return match ($node->commandName()) {

            'emph' => "<em>$body</em>",

            'textbf' => " <strong>$body</strong> ",

            'textit' => "<em>$body</em>",

            'texttt' => "<span style=\"font-family: monospace\">$body</span>",

            'textsc' => "<span style=\"font-variant: small-caps\">$body</span>",

            'textsf' => "<span style=\"font-family: sans-serif\">$body</span>",

            'textsl' => "<em>$body</em>",

            'textmd' => "<span style=\"font-weight: 500\">$body</span>",

            'textup', 'textnormal', 'textrm', 'text' => "<span style=\"font-variant: normal\">$body</span>",

            'textsuperscript' => "<sup>$body</sup>",

            'textsubscript' => "<sub>$body</sub>",

            'centerline' => "<div style=\"text-align: center\">$body</div>",

            'underline' => "<span style=\"text-decoration: underline\">$body</span>",

            'phantom' => "<span style=\"color: transparent\">$body</span>",

            default => "\\" . $node->commandName() . "{" . $body . "}"
        };
    }
}
