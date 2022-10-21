<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\CommandNode;

class FontCommandRenderer
{

    public static function renderNode(CommandNode $node, string $body = null): string
    {
        $body = $body ?? '';

        if ($node->ancestorOfType(['displaymath-environment', 'inlinemath', 'verbatim-environment'])) return "\\" . $node->commandName(). "{" . $body . "}";
        
        return match ($node->commandName()) {

            'emph' => " <em>$body</em> ",

            'textbf' => " <strong>$body</strong> ",

            'textit' => " <em>$body</em> ",

            'texttt' => " <code>$body</code> ",

            'textsc' => " <span style=\"font-variant: small-caps\">$body</span> ",

            'textsf' => " <span style=\"font-family: sans-serif\">$body</span> ",

            'textsl' => " <em>$body</em> ",

            'textmd' => " <span style=\"font-weight: 500\">$body</span> ",

            'textup' => " <span style=\"font-variant: normal\">$body</span> ",

            'textnormal' => " <span style=\"font-variant: normal\">$body</span> ",

            'text' => " <span style=\"font-variant: normal\">$body</span> ",

            'textsuperscript' => "<sup>$body</sup> ",

            'textsubscript' => "<sub>$body</sub> ",

            default => "\\" . $node->commandName(). "{" . $body . "}"

        };
    }
    
}
