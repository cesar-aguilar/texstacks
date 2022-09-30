<?php

namespace TexStacks;

use TexStacks\Parsers\LatexParser;
use TexStacks\Parsers\AuxParser;
use TexStacks\Parsers\LatexTree;
use TexStacks\Renderers\Renderer;

class LatexArticle
{

  private $latex_src;
  private $html_src;
  private $basename;
  private $dir;
  private $ref_labels;

  public $section_names = [];
  public $section_ids = [];
  public $section_labels = [];

  public function __construct(
    private $absolute_path,
    private LatexTree $tree,
    private Renderer $renderer
  ) {

    if (!file_exists($absolute_path)) {
      throw new \Exception("File not found: $absolute_path");
    }

    try {
      $this->latex_src = file_get_contents($absolute_path);
    } catch (\Exception $e) {
      throw new \Exception("Error reading file: $absolute_path");
    }

    $this->basename = basename($this->absolute_path, '.tex');
    $this->dir = dirname($this->absolute_path);

    $aux_path = $this->dir . DIRECTORY_SEPARATOR . $this->basename . '.aux';

    $this->ref_labels = (new AuxParser($aux_path))->getLabelsAsArray();

    $this->html_src = LatexParser::normalizeLatexSource($this->latex_src);

    $this->tree->build($this->html_src);
  }

  public function getLatex()
  {
    return $this->latex_src;
  }

  public function getHtml()
  {
    return $this->html_src;
  }

  public function convert()
  {
    return $this->renderer->renderTree($this->tree->root());
  }

  public function getRoot()
  {
    return $this->tree->root();
  }
}
