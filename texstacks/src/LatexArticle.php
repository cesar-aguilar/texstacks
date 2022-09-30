<?php

namespace TexStacks;

use TexStacks\Helpers\StrHelper;

class LatexArticle
{

  private $latex_src;
  private $html_src;
  private $basename;
  private $dir;
  private $aux_file;

  public $section_names = [];
  public $section_ids = [];
  public $section_labels = [];
  public $layout_graph;


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

    $this->aux_file = file_exists($aux_path) ? file_get_contents($aux_path) : null;

    $this->initializeHtmlSrc();
  }


  public function getLatex()
  {
    return $this->latex_src;
  }


  public function getHtml()
  {
    return $this->html_src;
  }

  public function initializeHtmlSrc()
  {

    $this->html_src = StrHelper::PluckIncludeDelimiters('\begin{document}', '\end{document}', $this->latex_src);
    $this->html_src = str_replace('\begin{document}', "\n", $this->html_src);
    $this->html_src = str_replace('\end{document}', "\n", $this->html_src);

    $this->html_src = StrHelper::DeleteLatexComments($this->html_src);

    // Replace less than and greater than symbols with latex commands
    $this->html_src = str_replace('<', '\lt', $this->html_src);
    $this->html_src = str_replace('>', '\gt', $this->html_src);

    // Replace $$...$$ with \[...\] and then $...$ with \(...\)
    $this->html_src = preg_replace('/\$\$(.*?)\$\$/s', '\\[$1\\]', $this->html_src);
    $this->html_src = preg_replace('/\$(.+?)\$/s', '\\($1\\)', $this->html_src);
  }

  public function convert()
  {

    $this->layout_graph = new LayoutGraph($this->html_src);

    // $this->convertSections();
  }


  // private function getSectionHtml($section_cmd, $section_name, $section_body, $label)
  // {

  //   $section_id = $label ? $label : StrHelper::Slugify($section_name);

  //   $this->section_names[] = $section_name;
  //   $this->section_ids[] = $section_id;
  //   $this->section_labels[] = $label;

  //   $container_tag = $section_cmd == 'chapter' ? 'article' : 'section';
  //   $heading_tag = $section_cmd == 'chapter' ? 'h1' : 'h2';

  //   return <<<END
  //   <$container_tag id="$section_id">
  //     <$heading_tag>$section_name</$heading_tag>
  //     $section_body
  //   </$container_tag>
  //   END;
  // }



}
