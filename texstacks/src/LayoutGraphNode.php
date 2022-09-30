<?php

namespace TexStacks;

class LayoutGraphNode
{

  private $parent;
  private $children = [];
  private $heading_label;
  private $heading_name;

  public function __construct(
    private $type,
    private $body,
    private $child_type = null,
    private $command = null
  ) {

    if ($command) {
      $this->setSectionNameAndLabel();
    }

    $this->is_leaf = $this->child_type === null;
  }

  public function addChild(LayoutGraphNode $child)
  {
    $this->children[] = $child;
  }

  public function setParent(LayoutGraphNode $parent)
  {
    $this->parent = $parent;
  }

  public function childType()
  {
    return $this->child_type;
  }

  public function body()
  {
    return $this->body;
  }

  public function isLeaf()
  {
    return !$this->childType();
  }

  public function headingLabel()
  {
    return $this->heading_label;
  }

  public function headingName()
  {
    return $this->heading_name;
  }

  private function setSectionNameAndLabel()
  {

    $this->heading_label = preg_match('/\\\\label\{(?<label>.+)\}/', $this->command, $match) ? $match['label'] : null;

    $section = preg_replace('/\\\\label\{.*\}/', '', $this->command);

    $sub_pattern = '/\\\\' . $this->type . '\{(?<name>.+?)\}/';

    $this->heading_name = preg_match($sub_pattern, $section, $match) ? $match['name'] : null;
  }
}
