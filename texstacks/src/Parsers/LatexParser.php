<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Node;
use TexStacks\Helpers\StrHelper;
use TexStacks\Parsers\SyntaxTree;
use TexStacks\Parsers\CommandNode;
use TexStacks\Parsers\SectionNode;
use TexStacks\Parsers\EnvironmentNode;

class LatexParser
{

  const AMS_MATH_ENVIRONMENTS = [
    'align', 'align*',
    'aligned',
    'alignedat', 'alignedat*',
    'alignat', 'alignat*',
    'flalign', 'flalign*',
    'array',
    'subarray',
    'cases',
    'CD',
    'gather', 'gather*',
    'gathered', 'gathered*',
    'equation', 'equation*',
    'eqnarray', 'eqnarray*',
    'multline', 'multline*',
    'split',
    'matrix',
    'pmatrix',
    'smallmatrix',
    'Bmatrix', 'bmatrix',
    'Vmatrix', 'vmatrix',
  ];

  const SECTION_COMMANDS = [
    'part',
    'chapter', 'chapter\*',
    'section', 'section\*',
    'subsection', 'subsection\*',
    'subsubsection', 'subsubsection\*',
    'paragraph', 'paragraph\*',
    'subparagraph', 'subparagraph\*',
  ];

  const LIST_ENVIRONMENTS = [
    'itemize',
    'enumerate',    
    'compactenum',
    'compactitem',
    'asparaenum',
  ];

  protected SyntaxTree $tree;  
  private $current_node;
  private $parsed_line;

  public function __construct()
  {
    $this->tree = new SyntaxTree();
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

  public function parse($latex_src_raw)
  {
    
    $lines = $this->getLines($latex_src_raw);

    /* Parse line and add node to syntax tree using depth-first traversal */
    foreach ($lines as $line) {
      
      $this->parsed_line = $this->parseLine($line);

      $parse_result = match($this->parsed_line['type']) {

        'section-cmd' => $this->handleSectionNode(),

        'environment',
        'math-environment',
        'list-environment' => $this->handleEnvironmentNode(),

        'item' => $this->handleListItemNode(),

        'label' => $this->handleLabelNode(),

        default => $this->addToCurrentNode(),

      };
      
    }
    
  }

  private function parseLine($line)
  {

    if (($match = $this->isBeginCmd($line)) || ($match = $this->isEndCmd($line))) {

      $content = $match['content'];
      $command_name = $match['command_name'];

      $options = preg_match('/\[(?<options>[^\]]*)\]/', $line, $match) ? $match['options'] : null;

      if (in_array($content, self::AMS_MATH_ENVIRONMENTS)) {
        $type = 'math-environment';
      }
      else if (in_array($content, self::LIST_ENVIRONMENTS)) {
        $type = 'list-environment';
      }
      else {
        $type = 'environment';
      }
      
      return [
        'type' => $type,
        'command_name' => $command_name,
        'command_content' => $content,
        'command_options' => $options,
        'command_label' => null,
        'command_src' => $line
      ];
    }    
    else if ($match = $this->isSectionCmd($line)) {

      $name = trim($match['name']);
      $content = preg_match('/\{(?<content>[^}]*)\}/', $line, $match) ? $match['content'] : null;      
      $options = preg_match('/\[(?<options>[^\]]*)\]/', $line, $match) ? $match['options'] : null;

      return [
        'type' => 'section-cmd',
        'command_name' => $name,
        'command_content' => $content,
        'command_options' => $options,
        'command_label' => null,
        'command_src' => $line
      ];
    }
    else if ($match = $this->isLabelCmd($line)) {

      return [
        'type' => 'label',
        'command_name' => 'label',
        'command_content' => $match['content'],
        'command_options' => null,
        'command_label' => null,
        'command_src' => $line
      ];
    }
    else if ($match = $this->isItemCmd($line)) {

      return [
        'type' => 'item',
        'command_name' => 'item',
        'command_content' => null,
        'command_options' => null,
        'command_label' => null,
        'command_src' => $line
      ];
    }
    else {
      
      return [
        'type' => 'text',
        'content' => $line
      ];
    }
  }

  private function addToCurrentNode()
  {
    $this->tree->addNode(new Node(
      [
        'id' => $this->tree->nodeCount(),
        'type' => 'text',
        'body' => $this->parsed_line['content']
      ]
    ), parent: $this->current_node); 
  }

  private function handleSectionNode()
  {
    $new_node = $this->createCommandNode();
    $parent = $this->current_node;

    /* Move up the tree until we find the first sectioning command
       with a lower numbered depth level */
    while ($parent->depthLevel() >= $new_node->depthLevel()) {
      $parent = $parent->parent();
    }
    
    $this->tree->addNode($new_node, $parent);
    $this->current_node = $new_node;
    return true;
  }

  private function handleEnvironmentNode()
  {
    if ($this->parsed_line['command_name'] === 'begin') {
      $new_node = $this->createCommandNode();
      $this->tree->addNode($new_node, $this->current_node);
      $this->current_node = $new_node;
      return true;
    }
    
    if ($this->parsed_line['type'] !== 'list-environment') {
      $this->current_node = $this->current_node->parent();
      return true;
    }

    /* If parsed_line was the end of a list-environment 
    then we need to move up the tree to find the first
    list-environment node
    */
    $parent = $this->current_node;

    while ($parent && $parent->type() !== 'list-environment') {
      $parent = $parent->parent();
    }
    
    $this->current_node = $parent->parent();

    return true;
    
  }

  private function handleListItemNode()
  {
    $new_node = $this->createCommandNode();

    $parent = $this->current_node;
    
    /* Move up the tree until we find the parent list-environment */
    while ($parent->type() !== 'list-environment') {
      $parent = $parent->parent();
    }

    $this->tree->addNode($new_node, $parent);

    $this->current_node = $new_node;

    return true;
  }

  private function handleLabelNode()
  {
    $this->current_node->setLabel($this->parsed_line['command_content']);

    if ($this->current_node->type() != 'section-cmd') {          
      $new_node = $this->createCommandNode();
      $this->tree->addNode($new_node, $this->current_node);
    }
    return true;
  }

  private function createCommandNode()
  {

    $args = ['id' => $this->tree->nodeCount(), ...$this->parsed_line];

    if ($this->parsed_line['type'] === 'section-cmd') {
      return new SectionNode($args);
    } 
    else if (preg_match('/environment/', $this->parsed_line['type'])) {
      return new EnvironmentNode($args);
    } 
    else 
    {
      return new CommandNode($args);
    }
  }

  private function getLines($latex_src_raw)
  {
    return array_map('trim', explode("\n", $this->normalizeLatexSource($latex_src_raw)));
  }

  private static function parseCommandAndLabel(string $name, string $str): array
  {

    $label = preg_match('/\\\\label\{(?<label>[^}]*)\}/', $str, $match) ? $match['label'] : null;

    $without_label = preg_replace('/\\\\label\{([^}]*)\}/', '', $str);

    $content = preg_match('/\\\\' . $name . '\{(?<content>[^}]*)\}/', $without_label, $match) ? $match['content'] : null;

    return ['content' => $content, 'label' => $label];
  }

  private static function parseCommandOptionsAndLabel(string $name, string $str): array
  {

    $label = preg_match('/\\\\label\{(?<label>[^}]*)\}/', $str, $match) ? $match['label'] : null;

    $without_label = preg_replace('/\\\\label\{[^}]*\}/', '', $str);

    $options = preg_match('/\[(?<options>[^}]*)\]/', $without_label, $match) ? $match['options'] : null;

    $without_options = preg_replace('/\[[^}]*\]/', '', $without_label);

    $content = preg_match('/\\\\' . $name . '\{(?<content>[^}]*)\}/', $without_options, $match) ? $match['content'] : null;

    return ['content' => $content, 'label' => $label, 'options' => $options];
  }

  private function normalizeLatexSource(string $latex_src): string
  {
    
    $html_src = preg_replace('/.*\\\begin\s*{document}[\s\n]*(.*)\\\end\s*{document}.*/sm', "$1", $latex_src);

    $html_src = StrHelper::DeleteLatexComments($html_src);

    // Replace less than and greater than symbols with latex commands
    $html_src = str_replace('<', ' \lt ', $html_src);
    $html_src = str_replace('>', ' \gt ', $html_src);

    // Replace $$...$$ with \[...\] and then $...$ with \(...\)
    $html_src = preg_replace('/\$\$(.*?)\$\$/s', '\\[$1\\]', $html_src);
    $html_src = preg_replace('/\$(.+?)\$/s', '\\($1\\)', $html_src);

    // Replace more than two newlines with two newlines
    $html_src = preg_replace('/\n{3,}/', "\n\n", $html_src);
    
    return $this->putCommandsOnNewLine($html_src);
  }

  private function putCommandsOnNewLine(string $latex_src): string
  {
    
    foreach (self::SECTION_COMMANDS as $command) {      
      $latex_src = preg_replace($this->cmdWithOptionsRegex($command), "\n$1$2\n", $latex_src);
    }

    $latex_src = preg_replace($this->envBeginRegex(), "\n$1$2\n", $latex_src);
    $latex_src = preg_replace('/' . $this->cmdRegex('end') . '/m', "\n$1\n", $latex_src);
    $latex_src = preg_replace('/' . $this->cmdRegex('label') . '/m', "\n$1\n", $latex_src);
    $latex_src = preg_replace('/' . $this->itemRegex() . '/m', "\n$1\n", $latex_src);    
    $latex_src = preg_replace($this->cmdWithOptionsRegex('includegraphics'), "\n$1$2\n", $latex_src);

    return trim($latex_src);
  }

  private function cmdWithOptionsRegex($command) {
    $sp = '[\s\n]*';
    $basic = $this->cmdRegex($command);
    $with_options = $sp . '(\\\\' . $command . '\s*\[[^\]]*\]\s*\{[^}]*\})' . $sp;
    $pattern = '/' . $with_options . '|' . $basic . '/m';
    return $pattern;
  }

  private function envBeginRegex() {
    $sp = '[\s\n]*';
    $basic = $this->cmdRegex('begin');
    $with_options = $sp . '(\\\\begin\s*\{[^}]*\}\s*\[[^\]]*\])' . $sp;
    $pattern = '/' . $with_options . '|' . $basic . '/m';
    return $pattern;
  }

  private function cmdRegex($command) {
    $sp = '[\s\n]*';
    $pattern = $sp . '(\\\\' . $command . '\s*\{[^}]*\})' . $sp;    
    return $pattern;
  }

  private function itemRegex() {
    $sp = '[\s\n]*';
    $command = 'item';
    $pattern = $sp . '(\\\\' . $command . '[^\s\n]*)' . $sp;
    return $pattern;
  }

  private function isBeginCmd($line)
  {
    return preg_match('/\\\\begin\{(?<content>[^}]*)\}/', $line, $match) ? [...$match, 'command_name' => 'begin'] : false;
  }

  private function isEndCmd($line)
  {
    return preg_match('/\\\\end\{(?<content>[^}]*)\}/', $line, $match) ? [...$match, 'command_name' => 'end'] : false;
  }

  private function isSectionCmd($line)
  {
    return preg_match('/\\\\(?<name>' . implode('|', self::SECTION_COMMANDS) . ')/', $line, $match) ? $match : false;
  }

  private function isLabelCmd($line)
  {
    return preg_match('/\\\\label\{(?<content>[^}]*)\}/', $line, $match) ? $match : false;
  }

  private function isItemCmd($line)
  {
    return preg_match('/\\\\item/', $line, $match) ? $match : false;
  }

}
