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

  protected SyntaxTree $tree;
  private $buffer = '';
  private $current_node;

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

  public function parse($latex_src_raw)
  {
    
    $lines = $this->getLines($latex_src_raw);
    
    foreach ($lines as $raw_line) {
      
      $parsedLine = $this->parseLine($raw_line);

      if ($parsedLine['type'] === 'text') {
        $this->buffer .= $parsedLine['content'];
        continue;
      }

      $this->addBufferToCurrentNode();

      if ($parsedLine['type'] === 'section-cmd') {

        $new_node = $this->createCommandNode($parsedLine);

        $parent = $this->current_node;

        while ($parent->depthLevel() >= $new_node->depthLevel()) {
          $parent = $parent->parent();
        }

        $this->tree->addNode($new_node, $parent);

        $this->current_node = $new_node;
      }
      else if ($this->isBeginEnvironment($parsedLine)) {

        $new_node = $this->createCommandNode($parsedLine);

        $this->tree->addNode($new_node, $this->current_node);

        $this->current_node = $new_node;
      }
      else if ($this->isEndEnvironment($parsedLine)) {

        $this->current_node = $this->current_node->parent();
      }
      else if ($parsedLine['type'] === 'label') {
        
        $this->current_node->setLabel($parsedLine['command_content']);

        if ($this->current_node->type() != 'section-cmd') {          
          $new_node = $this->createCommandNode($parsedLine);
          $this->tree->addNode($new_node, $this->current_node);
        }

      }
    }

    $this->addBufferToCurrentNode();
  }

  public function getRoot()
  {
    return $this->tree->root();
  }

  private function addBufferToCurrentNode()
  {

    if ($this->buffer == '') return;

    $this->tree->addNode(new Node(
      [
        'id' => $this->tree->nodeCount(),
        'type' => 'text',
        'body' => $this->buffer
      ]
    ), parent: $this->current_node);

    $this->buffer = '';
  }

  private function createCommandNode($parsedLine)
  {

    $args = ['id' => $this->tree->nodeCount(), ...$parsedLine];

    if ($parsedLine['type'] == 'section-cmd') {

      return new SectionNode($args);
    } else if (preg_match('/environment/', $parsedLine['type'])) {

      return new EnvironmentNode($args);
    } else {
      return new CommandNode($args);
    }
  }

  private function getLines($latex_src_raw)
  {
    return array_filter(array_map('trim', explode("\n", $this->normalizeLatexSource($latex_src_raw))));
  }

  private function parseLine($str)
  {

    if (preg_match('/^\\\\begin\{(?<content>[^}]*)\}/m', $str, $match)) {

      $content = trim($match['content']);      
      $options = preg_match('/\[(?<options>[^\]]*)\]/', $str, $match) ? $match['options'] : null;

      $type = in_array($content, self::AMS_MATH_ENVIRONMENTS) ? 'math-environment' : 'environment';

      return [
        'type' => $type,
        'command_name' => 'begin',
        'command_content' => $content,
        'command_options' => $options,
        'command_label' => null,
        'command_src' => $str
      ];
    } else if (preg_match('/^\\\\end\{(?<content>[^}]*)\}/m', $str, $match)) {

      $content = trim($match['content']);
      $type = in_array($content, self::AMS_MATH_ENVIRONMENTS) ? 'math-environment' : 'environment';

      return [
        'type' => $type,
        'command_name' => 'end',
        'command_content' => $match['content'],
        'command_options' => null,
        'command_label' => null,
        'command_src' => $str
      ];
    } else if (preg_match('/^\\\\(?<name>'. implode('|', self::SECTION_COMMANDS) .')/m', $str, $match)) {

      $name = trim($match['name']);
      $content = preg_match('/\{(?<content>[^}]*)\}/', $str, $match) ? $match['content'] : null;      
      $options = preg_match('/\[(?<options>[^\]]*)\]/', $str, $match) ? $match['options'] : null;

      return [
        'type' => 'section-cmd',
        'command_name' => $name,
        'command_content' => $content,
        'command_options' => $options,
        'command_label' => null,
        'command_src' => $str
      ];
    } 
    else if (preg_match('/\\\\label\{(?<content>[^}]*)\}/', $str, $match)) {
      return [
        'type' => 'label',
        'command_name' => 'label',
        'command_content' => $match['content'],
        'command_options' => null,
        'command_label' => null,
        'command_src' => $str
      ];
    }
    else {
      return [
        'type' => 'text',
        'content' => $str
      ];
    }
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
    $html_src = StrHelper::PluckIncludeDelimiters('\begin{document}', '\end{document}', $latex_src);
    $html_src = str_replace('\begin{document}', "\n", $html_src);
    $html_src = str_replace('\end{document}', "\n", $html_src);

    $html_src = StrHelper::DeleteLatexComments($html_src);

    // Replace less than and greater than symbols with latex commands
    $html_src = str_replace('<', ' \lt ', $html_src);
    $html_src = str_replace('>', ' \gt ', $html_src);

    // Replace $$...$$ with \[...\] and then $...$ with \(...\)
    $html_src = preg_replace('/\$\$(.*?)\$\$/s', '\\[$1\\]', $html_src);
    $html_src = preg_replace('/\$(.+?)\$/s', '\\($1\\)', $html_src);
    
    return $this->putCommandsOnNewLine($html_src);
  }

  private function putCommandsOnNewLine(string $latex_src): string
  {
    
    foreach (self::SECTION_COMMANDS as $command) {      
      $latex_src = preg_replace($this->sectionRegex($command), "\n$1$2\n", $latex_src);
    }

    $latex_src = preg_replace($this->envBeginRegex(), "\n$1$2\n", $latex_src);
    $latex_src = preg_replace('/' . $this->cmdRegex('end') . '/m', "\n$1\n", $latex_src);
    $latex_src = preg_replace('/' . $this->cmdRegex('label') . '/m', "\n$1\n", $latex_src);
    $latex_src = preg_replace('/' . $this->itemRegex() . '/m', "\n$1\n", $latex_src);
        
    return $latex_src;
  }

  private function isBeginEnvironment($parsedLine) {
    return preg_match('/environment/', $parsedLine['type']) && $parsedLine['command_name'] === 'begin';
  }

  private function isEndEnvironment($parsedLine) {
    return preg_match('/environment/', $parsedLine['type']) && $parsedLine['command_name'] === 'end';
  }

  private function sectionRegex($command) {
    $sp = '[\s|\n]*';
    $basic = $this->cmdRegex($command);
    $with_options = $sp . '(\\\\' . $command . '\s*\[[^\]]*\]\s*\{[^}]*\})' . $sp;
    $pattern = '/' . $with_options . '|' . $basic . '/m';
    return $pattern;
  }

  private function envBeginRegex() {
    $sp = '[\s|\n]*';
    $basic = $this->cmdRegex('begin');
    $with_options = $sp . '(\\\\begin\s*\{[^}]*\}\s*\[[^\]]*\])' . $sp;
    $pattern = '/' . $with_options . '|' . $basic . '/m';
    return $pattern;
  }

  private function cmdRegex($command) {
    $sp = '[\s|\n]*';
    $pattern = $sp . '(\\\\' . $command . '\s*\{[^}]*\})' . $sp;    
    return $pattern;
  }

  private function itemRegex() {
    $sp = '[\s|\n]*';
    $command = 'item';
    $pattern = $sp . '(\\\\' . $command . ')[^\s|\n]*' . $sp;    
    return $pattern;
  }

}
