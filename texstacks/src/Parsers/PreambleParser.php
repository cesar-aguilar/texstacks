<?php

namespace TexStacks\Parsers;

use TexStacks\Helpers\StrHelper;

class PreambleParser
{

  protected string $src;

  public function setSrc(string $src)
  {
    $this->src = $src;
  }
  
  public function getMathMacros() : string
  {

    $start = str_contains($this->src, "%\\begin{mathmacros}");
    $end = str_contains($this->src, "%\\end{mathmacros}");

    if ($start === false || $end === false) return '';
        
    return strip_tags(StrHelper::PluckExcludeDelimiters("%\\begin{mathmacros}", "%\\end{mathmacros}", $this->src));

  }

  private function getUsePackages() : array
  {
    $pattern = '/(\\\usepackage)/';

    preg_match_all($pattern, $this->src, $matches, PREG_OFFSET_CAPTURE);

    if (!isset($matches[0])) return [];

    $packages = [];

    foreach ($matches[0] as $match)
    {
      $offset = (int) $match[1];
      
      $command = trim(str_replace("\\", '', $match[0]));

      $args = StrHelper::getAllCmdArgsOptions($command, substr($this->src, $offset));

      $packages[] = $args;

    }
 
  }

  /**
   * Reads preamble of $latex_src and returns
   * array of \newtheorem declarations as objects
   */
  public function getTheoremEnvs() : array
  {

    preg_match_all('/(\\\newtheoremstyle|\\\newtheorem[*]?|\\\theoremstyle)/', $this->src, $matches, PREG_OFFSET_CAPTURE);

    if (!isset($matches[1])) return [];

    $thm_envs = [];
    
    $default_styles = ['plain', 'definition', 'remark'];
    $current_style = 'plain';

    foreach ($matches[1] as $match) {

      $command = str_replace("\\", '', $match[0]);
      $offset = $match[1];

      $args = StrHelper::getAllCmdArgsOptions($command, substr($this->src, $offset));

      if ($command === 'theoremstyle' && isset($args[0])) {
        $current_style = in_array($args[0]->value, $default_styles) ? $args[0]->value : 'plain';
        continue;
      }

      if ($command === 'newtheorem' || $command === 'newtheorem*')
      {

        $num_args = count($args);

        if ($num_args !== 2 && $num_args !== 3) continue;

        $env = $args[0]->type === 'arg' ? $args[0]->value : '';

        // Declaration of the form \newtheorem(*?){env}{text}
        if ($num_args === 2) {

          $text = $args[1]->type === 'arg' ? $args[1]->value : '';
          $parent = null;
          $shared = null;

        }
        // Declaration of the form 
        // Parent: \newtheorem{env}{text}[parent-counter] or
        // Shared: \newtheorem{env}[shared]{text}
        else {
        
          // Case Parent
          if ($args[1]->type === 'arg') {
            $text = $args[1]->value;
            $parent = $args[2]->value;
            $shared = null;
          }
          else
          { // Case Shared
            $shared = $args[1]->value;
            $text = $args[2]->value;
            $parent = null;
          }

        }

        $starred = $command === 'newtheorem*';

        $thm_envs[$env] = (object) [          
          'text' => $text,
          'parent' => $parent,
          'shared' => $shared,
          'style' => $current_style,
          'starred' => $starred,
        ];

      }

    }

    return $thm_envs;

  }
  
}