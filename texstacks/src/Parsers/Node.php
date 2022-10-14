<?php

namespace TexStacks\Parsers;

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

  protected Node|null $parent = null;
  protected array $children = [];
  protected int $id;
  protected string $type;
  protected string|null $body = null;
  protected bool $is_terminal;

  public function __construct($args)
  {
    $this->id = $args['id'];
    $this->type = $args['type'];
    $this->body = $args['body'] ?? null;
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

  public function leftSibling()
  {
    if ($this->parent() == null) return null;

    $siblings = $this->parent()->children();

    $index = array_search($this, $siblings);

    if ($index == 0) return null;

    return $siblings[$index - 1];
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

  public function ancestorOfType($type)
  {
    if ($this->parent === null) return false;

    $ancestor = $this->parent;

    $types = is_array($type) ? $type : [$type];

    while ($ancestor)
    {
      if (in_array($ancestor->type(), $types))
      {
        return true;
      }
      $ancestor = $ancestor->parent();
    }
    return false;
  }
}
