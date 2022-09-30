<?php

namespace TexStacks\Environments;

class LatexCommand
{
  public function __construct(public $name, public $content, public $label)
  {
  }

  public function get()
  {
  }
}
