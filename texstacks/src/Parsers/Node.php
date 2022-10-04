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
  protected int $id;
  protected string $type;
  protected string $body;
  protected bool $is_terminal;

  public function __construct($args)
  {
    $this->id = $args['id'];
    $this->type = $args['type'];
    $this->body = $args['body'] ?? '';
    $this->is_terminal = $args['is_terminal'] ?? false;
  }

  public function id()
  {
    return $this->id;
  }

  public function type()
  {
    return $this->type;
  }

  public function children()
  {
    return $this->children;
  }

  public function body()
  {
    return $this->body;
  }

  public function parent()
  {
    return $this->parent;
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
}
