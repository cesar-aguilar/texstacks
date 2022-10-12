<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class FontEnvironmentRenderer
{

    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {
        $body = $body ?? '';
                
        if ($node->ancestorOfType('math-environment')){
            return "\\" . $node->commandContent(). "{" . $body . "}";
        };

        if ($node->commandContent() === 'emph')
        {
            return " <em>$body</em> ";
        } 
        else if ($node->commandContent() === 'textbf')
        {
            return " <strong>$body</strong> ";
        }
        else if ($node->commandContent() === 'textit')
        {
            return " <em>$body</em> ";
        }
        else if ($node->commandContent() === 'texttt')
        {
            return " <code>$body</code> ";
        }
        else if ($node->commandContent() === 'textsc')
        {
            return " <span style=\"font-variant: small-caps\">$body</span> ";
        }
        else if ($node->commandContent() === 'textsf')
        {
            return " <span style=\"font-family: sans-serif\">$body</span> ";
        }
        else if ($node->commandContent() === 'textsl')
        {
            return " <em>$body</em> ";
        }
        else if ($node->commandContent() === 'textmd')
        {
            return " <span style=\"font-weight: 500\">$body</span> ";
        }
        else if ($node->commandContent() === 'textup')
        {
            return " <span style=\"font-style: normal\">$body</span> ";
        }
        else if ($node->commandContent() === 'textnormal')
        {
            return " <span style=\"font-style: normal\">$body</span> ";
        }
        else if ($node->commandContent() === 'text')
        {
            return " <span style=\"font-style: normal\">$body</span> ";
        }
        else if ($node->commandContent() === 'textsuperscript')
        {
            return " <sup>$body</sup> ";
        }
        else if ($node->commandContent() === 'textsubscript')
        {
            return " <sub>$body</sub> ";
        }        
        else
        {
            return "\\" . $node->commandContent(). "{" . $body . "}";
        }

        
    }
    
}
