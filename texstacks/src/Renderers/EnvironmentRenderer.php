<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class EnvironmentRenderer
{

    const AMS_THEOREM_ENVIRONMENTS = [
        'theorem', 'proposition', 'lemma',
        'corollary', 'definition'
    ];

    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {
        if (!trim($body)) return '';

        if (in_array($node->commandContent(), self::AMS_THEOREM_ENVIRONMENTS)) {
            return self::renderAmsEnvironment($node, $body);
        } else if ($node->commandContent() === 'proof') {
            return self::renderProofEnvironment($node, $body);
        } else if ($node->commandContent() === 'example') {
            return self::renderExampleEnvironment($node, $body);
        } else {
            return "$body";
        }
    }

    private static function renderAmsEnvironment(EnvironmentNode $node, string $body = null): string
    {
        $heading = ucwords($node->commandContent());

        return "<div class=\"ams-env\"><div class=\"ams-env-head {$node->commandContent()}\" id=\"{$node->commandLabel()}\">$heading</div><div class=\"ams-env-body\">$body</div></div>";
    }

    private static function renderProofEnvironment(EnvironmentNode $node, string $body = null): string
    {
        return "<div class=\"proof-env\"><div class=\"proof-head\" id=\"{$node->commandLabel()}\">Proof</div><div class=\"proof-body\">$body</div></div>";
    }

    private static function renderExampleEnvironment(EnvironmentNode $node, string $body = null): string
    {
        return "<div class=\"example-env\"><div class=\"example-head\" id=\"{$node->commandLabel()}\">Example</div><div class=\"example-body\">$body</div></div>";
    }
}
