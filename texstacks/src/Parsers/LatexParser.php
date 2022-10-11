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
    'math',
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
  ];

  protected SyntaxTree $tree;  
  private $current_node;
  private $parsed_line;
  private $line_number;

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
    // dd($lines);
    /* Parse line and add node to syntax tree using depth-first traversal */
    foreach ($lines as $number => $line) {

      $this->parsed_line = $this->parseLine($line, $number);

      $parse_result = match($this->parsed_line['type']) {

        'section-cmd' => $this->handleSectionNode(),

        'environment',
        'font-environment',
        'math-environment',
        'list-environment' => $this->handleEnvironmentNode(),

        'item' => $this->handleListItemNode(),

        'label' => $this->handleLabelNode(),

        'includegraphics' => $this->handleIncludeGraphicsNode(),
 
        default => $this->addToCurrentNode(),

      };
      
    }
    
  }

  private function parseLine($line, $number) {

    $commands = [
      'begin', 'end',
      ...self::SECTION_COMMANDS,
      'label',
      'item',      
      'includegraphics',
      ...self::FONT_COMMANDS,
    ];

    foreach ($commands as $command) {

      if ($match = $this->matchCommand($command, $line)) {
        return [...$match, 'line_number' => $number];
      }

    }

    return [
      'type' => 'text',
      'content' => $line,
      'line_number' => $number,
    ];

  }

  private function matchCommand($name, $line) {
    
    if (!preg_match('/^\\\\' . $name . '\s*/m', $line)) return false;

    $content = preg_match('/\{(?<content>[^}]*)\}/', $line, $match) ? $match['content'] : null; 
    
    $options = preg_match('/\[(?<options>[^\]]*)\]/', $line, $match) ? $match['options'] : null;

    $type = $this->getCommandType($name, $content);

    return [
      'type' => $type,
      'command_name' => $name,
      'command_content' => $content,
      'command_options' => $options,        
      'command_src' => $line
    ];

  }

  private function getCommandType($name, $content) {
    if ($name == 'begin' || $name == 'end') {
      if (in_array($content, self::AMS_MATH_ENVIRONMENTS))
      {
        return 'math-environment';
      }
      else if (in_array($content, self::LIST_ENVIRONMENTS))
      {
        return 'list-environment';
      }
      else if (in_array($content, self::FONT_COMMANDS))
      {
        return 'font-environment';
      }
      else
      {
        return 'environment';
      }
    }
    else if (in_array($name, self::SECTION_COMMANDS))
    {
      return 'section-cmd';
    }
    else if($name == 'figure')
    {
      return 'figure-environment';
    }
    else if ($name == 'includegraphics')
    {
      return 'includegraphics';
    }
    else if ($name == 'label')
    {
      return 'label';
    }
    else if ($name == 'caption')
    {
      return 'caption';
    }
    else if ($name == 'item')
    {
      return 'item';
    }
    else {
      return 'text';
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

    if ($this->current_node->type() === 'math-environment') {
      $new_node = $this->createCommandNode();
      $this->tree->addNode($new_node, $this->current_node);
    }
    return true;
  }

  private function handleIncludeGraphicsNode()
  {
    $new_node = $this->createCommandNode();
    $this->tree->addNode($new_node, $this->current_node);
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

  private function normalizeLatexSource(string $latex_src): string
  {

    $html_src = preg_replace('/.*\\\begin\s*{document}[\s\n]*(.*)\\\end\s*{document}.*/sm', "$1", $latex_src);

    $html_src = StrHelper::DeleteLatexComments($html_src);

    // Replace less than and greater than symbols with latex commands
    $html_src = str_replace('<', ' \lt ', $html_src);
    $html_src = str_replace('>', ' \gt ', $html_src);

    // Replace dollar sign with html entity
    $html_src = str_replace('\\$', ' &#36; ', $html_src);

    foreach (self::FONT_COMMANDS as $command) {
      $html_src = preg_replace($this->cmdContentRegex($command), $this->beginEndWrapper($command) , $html_src);
    }
    
    // Replace $$...$$ with \[...\]
    // $html_src = preg_replace('/\$\$(.*?)\$\$/s', '\\[$1\\]', $html_src);
    $html_src = preg_replace('/\$\$(.*?)\$\$/s', '\\begin{equation*}$1\\end{equation*}', $html_src);

    // Then replace $...$ with \(...\)
    // $html_src = preg_replace('/\$(.+?)\$/s', '\\($1\\)', $html_src);
    $html_src = preg_replace('/\$(.*?)\$/s', "\\begin{math}$1\\end{math}", $html_src);

    // Replace \[...\] with \begin{equation*}...\end{equation*}
    $html_src = preg_replace('/([^\\\])(?:\\\)(?:\[)/', '$1\\begin{equation*}', $html_src);
    $html_src = preg_replace('/^\s*(?:\\\)(?:\[)/m', '$1\\begin{equation*}', $html_src);
    $html_src = str_replace('\]', '\end{equation*}', $html_src);

    // Put labels on new line and make caption command an environment
    $html_src = preg_replace('/' . $this->cmdRegex('label') . '/m', "\n$1\n", $html_src);
    $html_src = preg_replace('/\\\caption\s*\{(?<content>.*)\}/', '\\begin{caption}$1\\end{caption}', $html_src);


    // Replace more than two newlines with two newlines
    $html_src = preg_replace('/\n{3,}/', "\n\n", $html_src);
    
    return $this->putCommandsOnNewLine($html_src);
  }

  private function putCommandsOnNewLine(string $latex_src): string
  {
    
    foreach (self::SECTION_COMMANDS as $command) {
      $latex_src = preg_replace($this->cmdWithOptionsRegex($command), "\n$1$2\n", $latex_src);
    }

    $latex_src = preg_replace($this->envBeginWithOptionsRegex(), "\n$1$2\n", $latex_src);

    $latex_src = preg_replace($this->envBeginRegex('math'), "\n$1\n", $latex_src);

    foreach (self::FONT_COMMANDS as $command) {
      $latex_src = preg_replace($this->envBeginRegex($command), "\n$1\n", $latex_src);
    }

    $latex_src = preg_replace('/' . $this->cmdRegex('end') . '/m', "\n$1\n", $latex_src);

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

  private function envBeginWithOptionsRegex() {
    $sp = '[\s\n]*';
    $basic = $this->cmdRegex('begin');
    $with_options = $sp . '(\\\\begin\s*\{[^}]*\}\s*\[[^\]]*\])' . $sp;
    $pattern = '/' . $with_options . '|' . $basic . '/m';
    return $pattern;
  }

  private function envBeginRegex($env) {
    $sp = '[\s\n]*';
    $pattern = '/' . $sp . '(\\\\begin{' . $env . '})' . $sp . '/m';
    return $pattern;
  }

  private function cmdRegex($command) {
    $sp = '[\s\n]*';
    $pattern = $sp . '(\\\\' . $command . '\s*\{[^}]*\})' . $sp;    
    return $pattern;
  }

  private function cmdContentRegex($command) {
    $sp = '[\s\n]*';
    $pattern = '/' . $sp . '\\\\' . $command . '\s*\{(.*?)\}' . $sp . '/m';
    return $pattern;
  }

  private function itemRegex() {
    $sp = '[\s\n]*';
    $command = 'item';
    $pattern = $sp . '(\\\\' . $command . '[^\s\n]*)' . $sp;
    return $pattern;
  }

  private function beginEndWrapper($command) {
    return '\\begin{'. $command .'}$1\\end{' . $command . '}';
  }

}
