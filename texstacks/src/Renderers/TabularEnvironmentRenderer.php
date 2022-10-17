<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\EnvironmentNode;

class TabularEnvironmentRenderer
{

    public static function renderNode(EnvironmentNode $node, string $body = null): string
    {        
        $body = $body ?? '';

        if ($node->ancestorOfType('verbatim'))
            return $node->commandSource() . $body . "\\end{{$node->commandContent()}}";

        if ($node->commandContent() !== 'array')
        {
            return self::renderTabularEnvironment($node, $body);
        } 

        return self::renderArrayEnvironment($node, $body);
        
    }

    private static function renderTabularEnvironment(EnvironmentNode $node, string $body = null): string
    {                
        $body = str_replace('\hline', '', $body);
        
        $rows = self::getRows($body);
        
        $table = '';
 
        foreach ($rows as $row) {
            $table .= "<tr>" . implode('', array_map(fn($x) => "<td>$x</td>", $row)) . "</tr>";
        }

        return "<table><tbody>$table</tbody></table>";
    }

    private static function getRows($text) : array
    {

        // Replace \\[1em] with \\
        $text = preg_replace('/(\\\)(\\\)\[(.*?)\]/m', "\\\\\\", $text);
        
        $rows = array_filter(array_map(trim(...), explode('\\\\', $text)));        

        return array_map(fn($row) => array_map(trim(...), explode('&', $row)), $rows);

    }

    private static function renderArrayEnvironment(EnvironmentNode $node, string $body = null): string
    {        
        $body = $body ?? '';

        // Just remove \hline commands and render the rest as an
        // arran environment which should be contained in a math environment
        $body = str_replace('\hline', '', $body);
        
        return  $node->commandSource() . $body . "\\end{{$node->commandContent()}}";
    
    }
    
}
