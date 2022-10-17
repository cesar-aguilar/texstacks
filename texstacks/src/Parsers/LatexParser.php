<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Node;
use TexStacks\Parsers\SyntaxTree;
use TexStacks\Parsers\CommandNode;
use TexStacks\Parsers\SectionNode;
use TexStacks\Parsers\EnvironmentNode;
use TexStacks\Parsers\LatexLexer;

class LatexParser
{

  protected SyntaxTree $tree;
  private $current_node;
  private $lexer;

  public function __construct($data=[])
  {
    $this->tree = new SyntaxTree();

    $lexer_data = [
      'thm_env' => $data['thm_env'] ?? [],
      'macros' => $data['macros'] ?? [],
    ];

    $this->lexer = new LatexLexer($lexer_data);

    $root = new SectionNode([
      'id' => 0,
      'type' => 'section-cmd',
      'command_name' => 'document'
    ]);

    $this->tree->setRoot($root);

    $this->current_node = $root;
  }

  public function getRoot()
  {
    return $this->tree->root();
  }

  public function setRefLabels($labels)
  {
    $this->lexer->setRefLabels($labels);
  }

  public function parse($latex_src_raw)
  {
   
    try {
      $tokens = $this->lexer->tokenize($latex_src_raw);
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
    // die($this->lexer->prettyPrintTokens());
    /* From token add node to syntax tree using depth-first traversal */
    foreach ($tokens as $token)
    {

      $handler = match($token->type) {

        'section-cmd' => 'handleSectionNode',

        'environment',
        'thm-environment',
        'displaymath-environment',
        'inlinemath',
        'tabular-environment',
        'list-environment' => 'handleEnvironmentNode',

        'item' => 'handleListItemNode',

        'label' => 'handleLabelNode',

        'includegraphics',
        'caption',
        'ref',
        'eqref',
        'cite',
        'font-cmd' => 'handleCommandNode',
 
        default => 'addToCurrentNode',

      };

      try {
        $this->$handler($token);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }
      
    }

    // dd($this->tree->root()->children());
    
  }

  private function addToCurrentNode($token)
  {
    $this->tree->addNode(new Node(
      [
        'id' => $this->tree->nodeCount(),
        'type' => 'text',
        'body' => $token->body,
      ]
    ), parent: $this->current_node); 
  }

  private function handleSectionNode($token)
  {
    $new_node = $this->createCommandNode($token);
    $parent = $this->current_node;

    /* Move up the tree until we find the first sectioning command
       with a lower numbered depth level */
    
    if (!method_exists($parent, 'depthLevel')) {
      throw new \Exception("Parse error on line number {$token->line_number} of original source file");
    }

    while ($parent->depthLevel() >= $new_node->depthLevel()) {
      $parent = $parent->parent();
      if (!method_exists($parent, 'depthLevel')) {
        throw new \Exception("Parse error on line number {$token->line_number} of original source file");
      }
    }
        
    $this->tree->addNode($new_node, $parent);
    $this->current_node = $new_node;
    return true;
  }

  private function handleEnvironmentNode($token)
  {
    if ($token->command_name === 'begin') {
      $new_node = $this->createCommandNode($token);
      $this->tree->addNode($new_node, $this->current_node);
      $this->current_node = $new_node;
      return true;
    }

    if ($token->type !== 'list-environment') {      
      $this->current_node = $this->current_node->parent();
      return true;
    }

    /* If token was the end of a list-env 
    then we need to move up the tree to find the first
    list-env node
    */
    $parent = $this->current_node;

    while ($parent && $parent->type() !== 'list-environment') {
      $parent = $parent->parent();
    }
    
    $this->current_node = $parent->parent();

    return true;
    
  }

  private function handleListItemNode($token)
  {
    $new_node = $this->createCommandNode($token);

    $parent = $this->current_node;
    
    /* Move up the tree until we find the parent list-env */
    while ($parent->type() !== 'list-environment') {
      $parent = $parent->parent();
    }

    $this->tree->addNode($new_node, $parent);

    $this->current_node = $new_node;

    return true;
  }

  private function handleLabelNode($token)
  {
    $this->current_node->setLabel($token->command_content);

    if ($this->current_node->type() === 'displaymath-environment') {
      $new_node = $this->createCommandNode($token);
      $this->tree->addNode($new_node, $this->current_node);
    }
    return true;
  }

  private function handleCommandNode($token)
  {
    $new_node = $this->createCommandNode($token);
    $this->tree->addNode($new_node, $this->current_node);
    return true;
  }

  private function createCommandNode($token)
  {
    
    $args = ['id' => $this->tree->nodeCount(), ...(array) $token];

    if ($token->type === 'section-cmd') {
      return new SectionNode($args);
    } 
    else if (preg_match('/environment/', $token->type)) {
      return new EnvironmentNode($args);
    }
    else
    {
      return new CommandNode($args);
    }
  }

  public function terminateWithError($message)
  {

    $node = new Node(
      [
        'id' => $this->tree->nodeCount(),
        'type' => 'text',
        'body' => "<span class=\"parse-error\">" . $message . "</span>",
      ]
      );

    $this->tree->addNode($node, parent: $this->current_node);
    $this->tree->prependNode($node);

  }

  // private function parseLine($line, $number) {

  //   $commands = [
  //     'begin',
  //     'end',
  //     ...self::SECTION_COMMANDS,
  //     'label',
  //     'item',      
  //     'includegraphics',
  //   ];

  //   foreach ($commands as $command) {

  //     if ($match = $this->matchCommand($command, $line)) {
  //       return [...$match, 'line_number' => $number];
  //     }

  //   }

  //   return [
  //     'type' => 'text',
  //     'content' => $line,
  //     'line_number' => $number,
  //   ];

  // }

  // private function matchCommand($name, $line) {
    
  //   if (!preg_match('/^\\\\' . $name . '\s*/m', $line)) return false;

  //   $content = preg_match('/\{(?<content>[^}]*)\}/', $line, $match) ? $match['content'] : null;

  //   $options = preg_match('/\[(?<options>[^\]]*)\]/', $line, $match) ? $match['options'] : null;

  //   $type = $this->getCommandType($name, $content);

  //   return [
  //     'type' => $type,
  //     'command_name' => $name,
  //     'command_content' => $content,
  //     'command_options' => $options,        
  //     'command_src' => $line
  //   ];

  // }
    
  // private function putCommandsOnNewLine(string $latex_src): string
  // {
    
  //   foreach (self::SECTION_COMMANDS as $command) {
  //     $latex_src = preg_replace($this->cmdWithOptionsRegex($command), "\n$1$2\n", $latex_src);
  //   }

  //   $latex_src = preg_replace($this->envBeginTabularRegex(), "\n$1$2\n", $latex_src);

  //   $latex_src = preg_replace($this->envBeginWithOptionsRegex(), "\n$1$2\n", $latex_src);

  //   $latex_src = preg_replace($this->envBeginNoOptionsRegex(), "\n$1$2\n", $latex_src);

  //   $latex_src = preg_replace($this->envBeginRegex('math'), "\n$1\n", $latex_src);

  //   foreach (self::FONT_COMMANDS as $command) {
  //     $latex_src = preg_replace($this->envBeginRegex($command), "\n$1\n", $latex_src);
  //   }

  //   $latex_src = preg_replace('/' . $this->cmdRegex('end') . '/m', "\n$1\n", $latex_src);

  //   $latex_src = preg_replace('/' . $this->itemRegex() . '/m', "\n$1\n", $latex_src);

  //   $latex_src = preg_replace($this->cmdWithOptionsRegex('includegraphics'), "\n$1$2\n", $latex_src);

  //   return trim($latex_src);
  // }

  // private function cmdWithOptionsRegex($command) {
  //   $sp = '[\s\n]*';
  //   $basic = $this->cmdRegex($command);
  //   $with_options = $sp . '(\\\\' . $command . '\s*\[[^\]]*\]\s*\{[^}]*\})' . $sp;
  //   $pattern = '/' . $with_options . '|' . $basic . '/m';
  //   return $pattern;
  // }

  // private function envBeginWithOptionsRegex()
  // {

  //   $sp = '[\s\n]*';
  //   $basic = $sp . '(\\\\begin\s*\{[a-z]+\}\s*\[[^\]]*\])(?!\{.*\})' . $sp;
  //   $with_options = $sp . '(\\\\begin\s*\{[a-z]+\}\s*\[[^\]]*\])(?!\{.*\})' . $sp;
  //   $pattern = '/' . $with_options . '|' . $basic . '/m';
  //   return $pattern;
  // }

  // private function envBeginNoOptionsRegex()
  // {
  //   $sp = '[\s\n]*';
  //   $pattern = $sp . '(\\\\begin\s*\{[a-z|*]+\})\s*(?!(\{.*\}|\[.*\]))' . $sp;
  //   return '/' . $pattern . '/m';
  // }

  // private function envBeginTabularRegex() {
  //   $sp = '[\s\n]*';
  //   $basic = $sp . '(\\\\begin\s*\{(?:tabular|array)\}\s*\{(.*)\})' . $sp;
  //   $with_options = $sp . '(\\\\begin\s*\{(?:tabular|array)\}\s*\[[a-z]*\]\s*\{(?:.*)\})' . $sp;
  //   $pattern = '/' . $with_options . '|' . $basic . '/m';
  //   return $pattern;
  // }

  // private function envBeginRegex($env) {
  //   $sp = '[\s\n]*';
  //   $pattern = '/' . $sp . '(\\\\begin{' . $env . '})' . $sp . '/m';
  //   return $pattern;
  // }

  // private function cmdRegex($command) {
  //   $sp = '[\s\n]*';
  //   $pattern = $sp . '(\\\\' . $command . '\s*\{[^}]*\})' . $sp;    
  //   return $pattern;
  // }

  // private function cmdContentRegex($command) {
  //   $sp = '[\s\n]*';
  //   $pattern = '/' . $sp . '\\\\' . $command . '\s*\{(.*?)\}' . $sp . '/m';
  //   return $pattern;
  // }

  // private function itemRegex() {
  //   $sp = '[\s\n]*';
  //   $command = 'item';
  //   $pattern = $sp . '(\\\\' . $command . '[^\s\n]*)' . $sp;
  //   return $pattern;
  // }

  // private function beginEndWrapper($command) {
  //   return '\\begin{'. $command .'}$1\\end{' . $command . '}';
  // }

}
