<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class GroupEnvironmentRenderer
{
    const FONT_COMMANDS = [
        'textrm',
        'textsf',
        'texttt',
        'textmd',
        'textbf',
        'textup',
        'textit',
        'textsl',
        'textsc',
        'emph',
        'textnormal',
        'textsuperscript',
        'textsubscript',
    ];
    
    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {
        $body = $body ?? '';

        $font_style = in_array($node->commandOptions(), self::FONT_COMMANDS) ? $node->commandOptions() : null;

        if ($node->ancestorOfType(['displaymath-environment', 'inlinemath', 'verbatim-environment'])) {

            if ($font_style) return "\\" . $font_style . "{" . $body . "}";

            return "{" . $body . "}";

        }

        return match ($font_style) {

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

            default => "<span class=\"grouping\">$body</span>"

        };

   

    }

}