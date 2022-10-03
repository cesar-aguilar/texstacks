<?php

namespace TexStacks\Renderers;

use TexStacks\Renderers\Renderer;
use TexStacks\Parsers\Node;

class EnvironmentRenderer extends Renderer {

    const AMS_ENIRONMENTS = ['theorem', 'proposition', 'lemma', 
    'corollary', 'definition'];

    protected function renderNode(Node $node, string $body = null): string
    {
        if (!trim($body)) return '';

        if (in_array($node->commandContent(), self::AMS_ENIRONMENTS)) {
            return $this->renderAmsEnvironment($node, $body);
        } else if ($node->commandContent() === 'proof') {
            return $this->renderProofEnvironment($node, $body);
        } else if($node->commandContent() === 'example') {
            return $this->renderExampleEnvironment($node, $body);
        }        
        else  {
            return "<div>$body</div>";
        }

    }

    private function renderAmsEnvironment(Node $node, string $body = null): string
    {
        $heading = ucwords($node->commandContent());

        return "<div class=\"ams-env\"><div class=\"ams-env-head {$node->commandContent()}\" id=\"{$node->commandLabel()}\">$heading</div><div class=\"ams-env-body\">$body</div></div>";
    }

    private function renderProofEnvironment(Node $node, string $body = null): string
    {
        return "<div class=\"proof-env\"><div class=\"proof-head\" id=\"{$node->commandLabel()}\">Proof</div><div class=\"proof-body\">$body</div></div>";
    }

    private function renderExampleEnvironment(Node $node, string $body = null): string
    {
        return "<div class=\"example-env\"><div class=\"example-head\" id=\"{$node->commandLabel()}\">Example</div><div class=\"example-body\">$body</div></div>";
    }

}