<?php

namespace TexStacks;

use TexStacks\Parsers\LatexParser;
use TexStacks\Parsers\AuxParser;
use TexStacks\Parsers\BaseLexer;
use TexStacks\Renderers\Renderer;
use TexStacks\Parsers\ArticleTokenLibrary;

class LatexTransformer
{
  
  private $parser;
  private $aux_parser;
  private $lexer;
  private $latex_src;
  private $aux_src;

  public $section_names = [];
  public $section_ids = [];
  public $section_labels = [];

  /**
   * 
   */
  public function __construct($latex_src='', $aux_src='')
  {

    $this->latex_src = $latex_src;
    $this->aux_src = $aux_src;

    try {      
      $this->aux_parser = new AuxParser($this->aux_src);
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    $ref_labels = $this->aux_parser->getLabelsAsArray();
    $citations = $this->aux_parser->getCitationsAsArray();
    
    $library = new ArticleTokenLibrary([
      'citations' => $citations,
      'ref_labels' => $ref_labels,
    ]);

    $this->lexer = new BaseLexer($library);

    $this->parser = new LatexParser(['latex_src' => $this->latex_src]);
    
  }

  /**
   * 
   */
  public function getLatex()
  {
    return $this->latex_src;
  }

  /**
   * 
   */
  public function transform()
  {

    try {
      $tokens = $this->lexer->tokenize($this->parser->getSrc());
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
    // dd($tokens);
    try {
      $this->parser->parse($tokens);
    } catch (\Exception $e) {
      $this->parser->terminateWithError("<div>Message: {$e->getMessage()}</div><div>File: {$e->getFile()}</div><div>Line: {$e->getLine()}</div>");
    }

    $body = trim(Renderer::render($this->parser->getRoot()));
    return preg_replace('/(\n[\s\t]*){2,}/', "<br><br>", $body);

  }

  /**
   * 
   */
  public function getFrontMatter()
  {
    $front_matter = $this->parser->getFrontMatter();

    $front_matter['title'] = isset($front_matter['title']) ? Renderer::render($front_matter['title']) : '';

    foreach ($front_matter['authors'] ?? [] as $author) {
      $author->name = Renderer::render($author->name);
    }

    foreach ($front_matter['thanks'] ?? [] as $k => $thanks) {
      $front_matter['thanks'][$k] = Renderer::render($thanks);
    }

    return $front_matter;
  }

  /**
   * 
   */
  public function getMathMacros()
  {
    return $this->parser->getMathMacros();
  }

  /**
   * 
   */
  public function getTheoremEnvs()
  {
    return $this->parser->getTheoremEnvs();
  }

  /**
   * 
   */
  public function getRoot()
  {
    return $this->parser->getRoot();
  }

}