<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\Node;
use TexStacks\Parsers\CommandNode;

class FontCommandRenderer
{

    public static function renderNode(CommandNode $node, string $body = null): string
    {

        if ($node->ancestorOfType(['environment:displaymath', 'inlinemath', 'environment:verbatim'])) return $node->commandSource();

        if ($node->commandContent() instanceof Node) {
            $body = Renderer::render($node->commandContent());
        }

        if ($node->commandName() === 'footnote') return self::renderFootnote($node, $body);

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

            default => "\\" . $node->commandName() . "{" . $body . "}"
        };
    }

    private static function renderFootnote($node, $body)
    {

        $num = $node->commandRefNum();

        $html = "<details class=\"footnote\"><summary class=\"footnote\">$num</summary><p class=\"footnote-content\">$body</p></details>";

        return $html;
    }
}
