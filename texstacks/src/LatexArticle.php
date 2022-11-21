<?php

namespace TexStacks;

use TexStacks\Parsers\LatexParser;
use TexStacks\Parsers\AuxParser;
use TexStacks\Parsers\LatexLexer;
use TexStacks\Renderers\Renderer;

class LatexArticle
{

  private $latex_src;
  private $basename;
  private $dir;
  private $parser;

  public $section_names = [];
  public $section_ids = [];
  public $section_labels = [];

  public function __construct(private $absolute_path)
  {

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

    $aux_parser = new AuxParser($aux_path);

    $ref_labels = $aux_parser->getLabelsAsArray();
    $citations = $aux_parser->getCitationsAsArray();

    $this->parser = new LatexParser(['latex_src' => $this->latex_src]);

    $this->lexer = new LatexLexer();
    $this->lexer->setRefLabels($ref_labels);
    $this->lexer->setCitations($citations);
    $this->lexer->setTheoremEnvs($this->parser->getTheoremEnvs());

    try {
      $tokens = $this->lexer->tokenize($this->parser->getSrc());
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    try {
      $this->parser->parse($tokens);
    } catch (\Exception $e) {
      $this->parser->terminateWithError("<div>Message: {$e->getMessage()}</div><div>File: {$e->getFile()}</div><div>Line: {$e->getLine()}</div>");
    }
  }

  public function getLatex()
  {
    return $this->latex_src;
  }

  public function convert()
  {
    return Renderer::render($this->getRoot());
  }

  public function getFrontMatter()
  {
    $front_matter = $this->parser->getFrontMatter();

    $front_matter['title'] = Renderer::render($front_matter['title']);

    foreach ($front_matter['authors'] as $author) {
      $author->name = Renderer::render($author->name);
    }

    return $front_matter;
  }

  public function getMathMacros()
  {
    return $this->parser->getMathMacros();
  }

  public function getTheoremEnvs()
  {
    return $this->parser->getTheoremEnvs();
  }

  public function getRefLabels()
  {
    return $this->lexer->getRefLabels();
  }

  public function getCitations()
  {
    return $this->lexer->getCitations();
  }

  public function getRoot()
  {
    return $this->parser->getRoot();
  }
}
