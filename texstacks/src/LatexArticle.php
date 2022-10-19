<?php

namespace TexStacks;

use TexStacks\Helpers\StrHelper;
use TexStacks\Parsers\LatexParser;
use TexStacks\Parsers\AuxParser;
use TexStacks\Renderers\Renderer;

class LatexArticle
{

  private $latex_src;
  private $html_src;
  private $basename;
  private $dir;

  public $section_names = [];
  public $section_ids = [];
  public $section_labels = [];

  public function __construct(
    private $absolute_path,
    public LatexParser $parser,
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

    $ref_labels = (new AuxParser($aux_path))->getLabelsAsArray();

    $this->parser->setRefLabels($ref_labels);

    $this->html_src = $this->latex_src;
    
    try {
      $this->parser->parse($this->html_src);
    } catch (\Exception $e) {
      $this->parser->terminateWithError($e->getMessage());
    }
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
    return $this->renderer->renderTree($this->getRoot());
  }

  public function getRoot()
  {
    return $this->parser->getRoot();
  }
}
