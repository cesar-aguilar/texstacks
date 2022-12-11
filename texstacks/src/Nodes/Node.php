<?php

namespace TexStacks\Nodes;

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
  public readonly int $id;
  public readonly string $type;
  public readonly string|null $body;
  public readonly int $line_number;
  protected array|null $class = [];

  public function __construct($args)
  {
    $this->id = $args['id'];
    $this->type = $args['type'];
    $this->body = $args['body'] ?? null;
    $this->line_number = $args['line_number'] ?? false;
  }

  public function children()
  {
    return $this->children;
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

  public function prependChild(Node $child)
  {
    array_unshift($this->children, $child);
  }

  public function setParent(Node $parent)
  {
    $this->parent = $parent;
  }

  public function setBody(string $body)
  {
    $this->body = $body;
  }

  public function hasType($type)
  {
    $types = is_array($type) ? $type : [$type];

    return in_array($this->type, $types);
  }

  public function ancestorOfType($type)
  {
    if ($this->parent === null) return false;

    $ancestor = $this->parent;

    $types = is_array($type) ? $type : [$type];

    while ($ancestor) {
      if ($ancestor->hasType($types)) {
        return true;
      }
      $ancestor = $ancestor->parent();
    }
    return false;
  }

  public function pathToRootHasType($type)
  {

    $types = is_array($type) ? $type : [$type];

    return in_array($this->type, $types) || $this->ancestorOfType($types);
  }

  /**
   * Traverse element towards the root and 
   * return the first node of type 
   * $type, if no such node then return null. 
   */
  public function closest($type)
  {

    if ($this->type === $type) return $this;

    $ancestor = $this->parent;

    while ($ancestor) {
      if ($ancestor->type === $type) {
        return $ancestor;
      }
      $ancestor = $ancestor->parent;
    }

    return null;
  }

  /**
   * Find child with given type
   */
  public function findChild($type)
  {
    foreach ($this->children as $child) {
      if ($child->hasType($type)) return $child;
    }
    return null;
  }

  /**
   * Find first descendant with given type
   */
  public function findDescendant($type) {
    foreach ($this->children as $child) {
      if ($child->hasType($type)) return $child;
      $descendant = $child->findDescendant($type);
      if ($descendant) return $descendant;
    }
    return null;
  }

  /**
   * Find all descendants with given type
   */
  public function findAllDescendants($type) {
    $descendants = [];
    foreach ($this->children as $child) {
      if ($child->hasType($type)) $descendants[] = $child;
      $descendants = array_merge($descendants, $child->findAllDescendants($type));
    }
    return $descendants;
  }

  public function addClass($name)
  {

    if (in_array($name, $this->class)) return;

    $this->class[] = $name;
  }

  public function getClasses()
  {
    return implode(' ', $this->class);
  }

  public function hasClasses()
  {
    return !empty($this->class);
  }

  public function render($body): string
  {
    // return preg_replace('/(\n[\s\t]*){2,}/', "<br><br>", $body);

    if ($body === "\n") return '';

    return $body;
  }
}
