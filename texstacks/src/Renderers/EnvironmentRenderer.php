<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class EnvironmentRenderer
{

    const AMS_THEOREM_ENVIRONMENTS = [
        'theorem',
        'proposition',
        'lemma',
        'corollary',
        'definition',
        'conjecture',
    ];

    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {
        $body = $body ?? '';

        if (in_array($node->commandContent(), self::AMS_THEOREM_ENVIRONMENTS))
            return self::renderTheoremEnvironment($node, $body);

        return match ($node->commandContent()) {

            'proof' => self::renderProofEnvironment($node, $body),

            'example' => self::renderExampleEnvironment($node, $body),

            'figure' => self::renderFigureEnvironment($node, $body),

            'table' => self::renderTableEnvironment($node, $body),

            'caption' => self::renderCaptionEnvironment($node, $body),

            default => self::renderUnknownEnvironment($node, $body)
        };

    }

    private static function renderTheoremEnvironment(EnvironmentNode $node, string $body = null): string
    {
        $heading = ucwords($node->commandContent());

        return "<div class=\"thm-env\"><div class=\"thm-env-head {$node->commandContent()}\" id=\"{$node->commandLabel()}\">$heading</div><div class=\"thm-env-body\">$body</div></div>";
    }

    private static function renderProofEnvironment(EnvironmentNode $node, string $body = null): string
    {
        return "<div class=\"proof-env\"><div class=\"proof-head\" id=\"{$node->commandLabel()}\">Proof</div><div class=\"proof-body\">$body <span style=\"font-variant: small-caps\">QED</span></div></div>";
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
        return "<div class=\"unknown-env\"><div class=\"unknown-env-head\" id=\"{$node->commandLabel()}\">Name: <strong>{$node->commandContent()}</strong></div><div class=\"unknown-env-body\">$body</div></div>";
    }

    private static function renderTableEnvironment(EnvironmentNode $node, string $body = null): string
    {
        return "<div class=\"table-container\" id=\"{$node->commandLabel()}\">$body</div>";
    }

    private static function renderCaptionEnvironment(EnvironmentNode $node, string $body = null): string
    {
        return match ($node->parent()->commandContent()) {

            'figure' => "<figcaption>$body</figcaption>",

            'table' => "<div class=\"table-caption\">$body</div>",

            default => "<div class=\"{$node->parent()->commandContent()}-caption\">$body</div>",

        };
    }

}
