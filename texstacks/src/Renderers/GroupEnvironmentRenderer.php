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
        'footnote',
    ];
    
    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {
        $body = $body ?? '';

        $font_style = in_array($node->commandOptions(), self::FONT_COMMANDS) ? $node->commandOptions() : null;

        if ($node->ancestorOfType(['displaymath-environment', 'inlinemath', 'verbatim-environment'])) {

            if ($font_style) return " \\" . $font_style . "{" . $body . "} ";

            return " {" . $body . "} ";

        }

        $tag = match ($font_style) {

            'emph' => ['tag' => 'em'],

            'textbf' => ['tag' => 'strong'],

            'textit' => ['tag' => 'em'],

            'texttt' => ['tag' => 'code'],

            'textsc' => ['tag' => 'span', 'style' => ['font-variant' => 'small-caps']],

            'textsf' => ['tag' => 'span', 'style' => ['font-variant' => 'sans-serif']],

            'textsl' => ['tag' => 'em'],

            'textmd' => ['tag' => 'span', 'style' => ['font-weight' => '500']],

            'textup' => ['tag' => 'span', 'style' => ['font-variant' => 'normal']],

            'textnormal' => ['tag' => 'span', 'style' => ['font-variant' => 'normal']],

            'text' => ['tag' => 'span', 'style' => ['font-variant' => 'normal']],

            'textsuperscript' => ['tag' => 'sup'],

            'textsubscript' => ['tag' => 'sub'],

            'footnote' => 'footnote',

            default => ['tag' => 'span'],

        };

        if ($tag === 'footnote') return self::renderFootnote($node, $body);

        $html = " <{$tag['tag']}";

        if (isset($tag['style'])) {
            $html .= " style=\"";
            foreach ($tag['style'] as $name => $value) {
                $html .= "$name:$value;";
            }
            $html .= '"';
        }

        if ($node->hasClasses()) {
            $html .= " class=\"{$node->getClasses()}\"";
        }

        $html .= ">$body</{$tag['tag']}> ";

        return $html;

    }

    private static function renderFootnote($node, $body) {

        $num = $node->commandRefNum();

        $html = "<details class=\"footnote\"><summary class=\"footnote\">$num</summary><p class=\"footnote-content\">$body</p></details>";

        return $html;

    }

}