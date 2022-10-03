<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\LatexParser;

/**
 * A node in the graph of the LaTeX document
 * Could represent a LaTeX command with possibly a label: 
 * 
 *  latex_command = \command{content}\label{label}
 * 
 * name: the name of the command (\command)
 * content: the content of the command (content)
 * label: the label of the command (label)
 * 
 * type: layout, environment, or text (in which case latex_command is null)
 * body: the LaTeX source within the node or empty if children are present
 */
class Node
{

  protected Node $parent;
  protected array $children = [];
  protected string|null $command_label = '';
  protected string|null $command_content = '';
  protected string|null $command_options = '';
  
  public function __construct(
    protected int $id,
    protected string $type,
    protected string $body,
    protected string|null $command_name = null,
    protected string|null  $latex_command = null,    
  ) {

    if ($latex_command) {
      $this->setCommandContentAndLabel();
    }

    $this->init();
  }

  public function id()
  {
    return $this->id;
  }

  public function type()
  {
    return $this->type;
  }

  public function commandName()
  {
    return $this->command_name;
  }

  public function commandLabel()
  {
    return $this->command_label;
  }

  public function commandContent()
  {
    return $this->command_content;
  }

  public function children()
  {
    return $this->children;
  }
  
  public function body()
  {
    return $this->body;
  }

  public function isLeaf()
  {
    return empty($this->children);
  }

  public function isText()
  {
    return $this->type === 'text';
  }

  public function addChild(Node $child)
  {
    $this->children[] = $child;
  }

  public function setParent(Node $parent)
  {
    $this->parent = $parent;
  }

  public function setBody(string $body)
  {
    $this->body = $body;
  }

  protected function setCommandContentAndLabel()
  {

    $result = LatexParser::parseCommandOptionsAndLabel(name: $this->command_name, str: $this->latex_command);

    $this->command_label = $result['label'];
    $this->command_content = $result['content'];
    $this->command_options = $result['options'];
  }

  protected function init()
  {
  }
}
