<?php

namespace TexStacks\Renderers;

use TexStacks\Nodes\Node;
use TexStacks\Renderers\Renderer;
use TexStacks\Nodes\EnvironmentNode;

class EnvironmentRenderer
{

    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {

        if ($node->ancestorOfType(['environment:displaymath', 'inlinemath', 'environment:verbatim']))
            return $node->commandSource() . $body . "\\end{{$node->commandContent()}}";

        return match ($node->commandContent()) {

            'document' => $body,

            'center' => "<div style=\"text-align: center\">$body</div>",

            'proof' => self::renderProofEnvironment($node, $body),

            'example' => self::renderExampleEnvironment($node, $body),

            'figure' => self::renderFigureEnvironment($node, $body),

            'table' => self::renderTableEnvironment($node, $body),

            'abstract' => self::renderAbstract($node, $body),

            'quote', 'quotation' => self::renderQuoteEnvironment($node, $body),

            'verbatim' => "<pre>$body</pre>",

            default => self::renderUnknownEnvironment($node, $body)
        };
    }

    private static function renderProofEnvironment(EnvironmentNode $node, string $body = null): string
    {
        $head = 'Proof';

        if ($node->commandOptions() instanceof Node) {
            $head = Renderer::render($node->commandOptions());
        }

        return "<div class=\"proof-env\"><span class=\"proof-head\" id=\"{$node->commandLabel()}\">$head. </span>&nbsp; $body <span style=\"font-variant: small-caps\">QED</span></div>";
    }

    private static function renderQuoteEnvironment(EnvironmentNode $node, string $body = null): string
    {
        return "<blockquote>$body</blockquote>";
    }

    private static function renderExampleEnvironment(EnvironmentNode $node, string $body = null): string
    {
        return "<div class=\"example-env\"><div class=\"example-head\" id=\"{$node->commandLabel()}\">Example</div><div class=\"example-body\">$body</div></div>";
    }

    private static function renderFigureEnvironment(EnvironmentNode $node, string $body = null): string
    {
        return "<figure id=\"{$node->commandLabel()}\">$body</figure>";
    }

    private static function renderUnknownEnvironment(EnvironmentNode $node, string $body = null): string
    {
        if ($node->ancestorOfType(['environment:displaymath', 'environment:tabular'])) return $body;

        return "<div class=\"unknown-env\"><div class=\"unknown-env-head\" id=\"{$node->commandLabel()}\">Name: <strong>{$node->commandContent()}</strong></div><div class=\"unknown-env-body\">$body</div></div>";
    }

    private static function renderTableEnvironment(EnvironmentNode $node, string $body = null): string
    {
        return "<div class=\"table-container\" id=\"{$node->commandLabel()}\">$body</div>";
    }

    private static function renderAbstract(EnvironmentNode $node, string $body = null): string
    {
        return "<div class=\"abstract-container\"><div class=\"abstract-head\">Abstract</div><div class=\"abstract-body\">$body</div></div>";
    }
}
