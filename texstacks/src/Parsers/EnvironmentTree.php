<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Node;
use TexStacks\Parsers\EnvironmentNode;
use TexStacks\Parsers\LatexTree;

class EnvironmentTree extends LatexTree
{

  public function build($latex_src)
  {
    $this->root = new Node(
      id: 0,
      type: 'layout',
      body: $latex_src
    );
    
    $this->addNode($this->root);
  }

  protected function addNode($node, $parent = null)
  {
    $this->nodes[] = $node;

    if ($parent) {
      $node->setParent($parent);
    }

    // Base case
    if ($node->isText()) {
      return;
    }
    
    // Recursive case: Split the node body into child nodes
    $lines = explode("\n", $node->body());
    
    $buffer = '';

    $counter = 0;

    $current_env = '';
        
    foreach ($lines as $line) {

      $line = trim($line);
      
      if (preg_match('/^\\\\begin\{(?<env>[^}]*)\}/m', $line, $match)) {

        $match['env'] = trim($match['env']);
                
        if ($current_env === '') {
          $current_env = $match['env'];

          $child_node = new Node(
            id: count($this->nodes),
            type: 'text',
            body: trim($buffer)
          );

          $node->addChild($child_node);
          $this->addNode($child_node, parent: $node);

          $current_node = new EnvironmentNode(
            id: count($this->nodes),
            type: 'env',
            body: '',
            command_name: 'begin',
            latex_command: $line            
          );

          $counter++;

          $buffer = '';

        } else  {
          $buffer .= $line . "\n";
          $counter += (int)($current_env === $match['env'] ? 1 : 0);
        }
        
      } else if (preg_match('/^\\\\end\{(?<env>[^}]*)\}/m', $line, $match)) {
        
        $match['env'] = trim($match['env']);
        
        if ($current_env === $match['env']) {
          $counter--;
        }

        if ($counter === 0) {

          $current_env = '';

          // Close environment node with body = $buffer
          // and make recursive call
          $current_node->setBody($buffer);
          $node->addChild($current_node);
          $this->addNode($current_node, parent: $node);
                    
          $buffer = '';          

        } else {
          $buffer .= $line . "\n";
        }

      } else {
        $buffer .= $line . "\n";
      }

    }

    // If anything in buffer then create text node
    if ($buffer) {
      $child_body = trim($buffer);
      $child_node = new Node(
        id: count($this->nodes),
        type: 'text',
        body: $child_body
      );

      $node->addChild($child_node);
      $this->addNode($child_node, $node);
    }
    
  }
}
