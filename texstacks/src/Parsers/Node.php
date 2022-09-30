<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\LatexParser;

/**
 * A node in the graph of the LaTeX document
 * Represents a LaTeX command with possibly a label: 
 * 
 *  latex_command = \command{content}\label{label}
 * 
 * name: the name of the command (\command)
 * content: the content of the command (content)
 * label: the label of the command (label)
 * 
 * type: layout, environment, or text (in which case latex_command is null)
 * body: the LaTeX source within the node
 */
class Node
{

  protected Node $parent;
  protected array $children = [];
  protected string|null $command_label = '';
  protected string|null $command_content = '';
  protected bool $is_leaf = false;

  public function __construct(
    protected int $index,
    protected string $type,
    protected string $body,
    protected string|null $name = null,
    protected string|null  $latex_command = null,
    protected string|null $child_name = null
  ) {

    if ($latex_command) {
      $this->setCommandContentAndLabel();
    }

    $this->is_leaf = $this->child_name === null;

    $this->init();
  }

  public function index()
  {
    return $this->index;
  }

  public function type()
  {
    return $this->type;
  }

  public function name()
  {
    return $this->name;
  }

  public function children()
  {
    return $this->children;
  }

  public function childName()
  {
    return $this->child_name;
  }

  public function body()
  {
    return $this->body;
  }

  public function isLeaf()
  {
    return $this->is_leaf;
  }

  public function commandLabel()
  {
    return $this->command_label;
  }

  public function commandContent()
  {
    return $this->command_content;
  }

  public function addChild(Node $child)
  {
    $this->children[] = $child;
  }

  public function setParent(Node $parent)
  {
    $this->parent = $parent;
  }

  protected function setCommandContentAndLabel()
  {

    $result = LatexParser::parseCommandAndLabel(name: $this->name, str: $this->latex_command);

    $this->command_label = $result['label'];
    $this->command_content = $result['content'];
  }

  protected function init()
  {
  }
}
