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
      } else if ($parsedLine['type'] === 'environment' && $parsedLine['command_name'] === 'begin') {

        $new_node = $this->createCommandNode($parsedLine);

        $this->tree->addNode($new_node, $this->current_node);

        $this->current_node = $new_node;
      } else if ($parsedLine['type'] === 'environment' && $parsedLine['command_name'] === 'end') {

        $this->current_node = $this->current_node->parent();
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
    } else if ($parsedLine['type'] == 'environment') {

      return new EnvironmentNode($args);
    }
  }

  private function getLines($latex_src_raw)
  {
    return array_map('trim', explode("\n", $this->normalizeLatexSource($latex_src_raw)));
  }

  private function parseLine($str)
  {

    if (preg_match('/^\\\\begin\{(?<content>[^}]*)\}/m', $str, $match)) {

      $content = trim($match['content']);
      $label = preg_match('/\\\\label\{(?<label>[^}]*)\}/', $str, $match) ? $match['label'] : null;
      $without_label = preg_replace('/\\\\label\{[^}]*\}/', '', $str);
      $options = preg_match('/\[(?<options>[^\]]*)\]/', $without_label, $match) ? $match['options'] : null;
      return [
        'type' => 'environment',
        'command_name' => 'begin',
        'command_content' => $content,
        'command_options' => $options,
        'command_label' => $label,
        'command_src' => $str
      ];
    } else if (preg_match('/^\\\\end\{(?<content>[^}]*)\}/m', $str, $match)) {

      return [
        'type' => 'environment',
        'command_name' => 'end',
        'command_content' => $match['content'],
        'command_options' => null,
        'command_label' => null,
        'command_src' => $str
      ];
    } else if (preg_match('/^\\\\(?<name>chapter|section|subsection|subsubsection)\{(?<content>[^}]*)\}/m', $str, $match)) {

      $name = trim($match['name']);
      $content = trim($match['content']);
      $label = preg_match('/\\\\label\{(?<label>[^}]*)\}/', $str, $match) ? $match['label'] : null;
      $without_label = preg_replace('/\\\\label\{[^}]*\}/', '', $str);
      $options = preg_match('/\[(?<options>[^\]]*)\]/', $without_label, $match) ? $match['options'] : null;

      return [
        'type' => 'section-cmd',
        'command_name' => $name,
        'command_content' => $content,
        'command_options' => $options,
        'command_label' => $label,
        'command_src' => $str
      ];
    } else {
      return [
        'type' => 'text',
        'content' => $str
      ];
    }
  }

  public static function parseCommandAndLabel(string $name, string $str): array
  {

    $label = preg_match('/\\\\label\{(?<label>[^}]*)\}/', $str, $match) ? $match['label'] : null;

    $without_label = preg_replace('/\\\\label\{([^}]*)\}/', '', $str);

    $content = preg_match('/\\\\' . $name . '\{(?<content>[^}]*)\}/', $without_label, $match) ? $match['content'] : null;

    return ['content' => $content, 'label' => $label];
  }

  public static function parseCommandOptionsAndLabel(string $name, string $str): array
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
    $commands = ['begin', 'end', 'chapter', 'section', 'subsection', 'subsubsection'];

    foreach ($commands as $command) {
      $latex_src = preg_replace('/(.+)\\\\' . $command . '\{([^}]*)\}/m', "$1\n\\$command{" . "$2" . "}", $latex_src);
    }

    return $latex_src;
  }
}
