<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\Node;
use TexStacks\Parsers\CommandNode;

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

            'texttt' => "<code>$body</code>",

            'textsc' => "<span style=\"font-variant: small-caps\">$body</span>",

            'textsf' => "<span style=\"font-family: sans-serif\">$body</span>",

            'textsl' => "<em>$body</em>",

            'textmd' => "<span style=\"font-weight: 500\">$body</span>",

            'textup' => "<span style=\"font-variant: normal\">$body</span>",

            'textnormal' => "<span style=\"font-variant: normal\">$body</span>",

            'text' => "\\text\{$body\}",

            'textsuperscript' => "<sup>$body</sup>",

            'textsubscript' => "<sub>$body</sub>",

            'centerline' => "<div style=\"text-align: center\">$body</div>",

            default => "\\" . $node->commandName() . "{" . $body . "}"
        };
    }

}
