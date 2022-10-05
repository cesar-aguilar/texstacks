<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Node;
use TexStacks\Parsers\LatexParser;

/**
 * A node in the graph of the LaTeX document
 * Could represent a LaTeX command with possibly a label: 
 * 
 *  command_src = \command{content}\label{label}
 * 
 * name: the name of the command (\command)
 * content: the content of the command (content)
 * label: the label of the command (label)
 * 
 * type: layout, environment, or text (in which case command_src is null)
 * body: the LaTeX source within the node or empty if children are present
 */
class CommandNode extends Node
{

  protected string|null $command_src = '';
  protected string|null $command_name = '';
  protected string|null $command_label = '';
  protected string|null $command_content = '';
  protected string|null $command_options = '';

  public function __construct($args)
  {

    parent::__construct($args);

    $this->command_src = $args['command_src'] ?? '';
    $this->command_name = $args['command_name'] ?? '';
    $this->command_label = $args['command_label'] ?? '';
    $this->command_content = $args['command_content'] ?? '';
    $this->command_options = $args['command_options'] ?? '';
  }

  public function commandSource()
  {
    return $this->command_src;
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

  public function commandOptions()
  {
    return $this->command_options;
  }

  public function setLabel($label)
  {
    $this->command_label = $label;
  }
}
