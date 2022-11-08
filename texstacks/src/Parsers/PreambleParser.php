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

  public function getMathMacros(): string
  {
    /* Match macros should be contained within these delimiters in the preamble */
    $left_delim = "%\\begin{mathmacros}";
    $right_delim = "%\\end{mathmacros}";

    $start = str_contains($this->src, $left_delim);
    $end = str_contains($this->src, $right_delim);

    if ($start === false || $end === false) return '';

    $src = StrHelper::PluckExcludeDelimiters($left_delim, $right_delim, $this->src);

    return StrHelper::DeleteLatexComments($src, replace: '');
  }

  private function getUsePackages(): array
  {
    $pattern = '/(\\\usepackage)/';

    preg_match_all($pattern, $this->src, $matches, PREG_OFFSET_CAPTURE);

    if (!isset($matches[0])) return [];

    $packages = [];

    foreach ($matches[0] as $match) {
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
  public function getTheoremEnvs(): array
  {

    $pattern = '/(\\\newtheoremstyle|\\\newtheorem[*]?|\\\theoremstyle)/';

    preg_match_all($pattern, $this->src, $matches, PREG_OFFSET_CAPTURE);

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

      if ($command === 'newtheorem' || $command === 'newtheorem*') {

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
          } else { // Case Shared
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

  public function getFrontMatter(): array
  {
    return [
      'title' => $this->getArticleTitle(),
      'authors' => $this->getArticleAuthors(),
      'date' => $this->getArticleDate(),
    ];
  }

  private function getArticleTitle(): string
  {
    // return StrHelper::getCmdArg("title", $this->src);
    $title_options = StrHelper::getAllCmdArgsOptions("title", $this->src);

    foreach ($title_options as $arg) {
      if ($arg->type === 'arg') return $arg->value;
    }

    return '';
  }

  private function getArticleDate(): string
  {
    return StrHelper::getCmdArg("date", $this->src);
  }

  private function getArticleAuthors(): array
  {

    $author_args = StrHelper::getAllCmdArgsOptions("author", $this->src);

    if (empty($author_args)) return [];

    $authors = [];

    $authors_arr = $author_args[0]->type === 'arg' ? explode("\\and", $author_args[0]->value) : [];

    foreach ($authors_arr as $author) {
      $author = StrHelper::cleanUpText($author);

      $thanks_arg = StrHelper::getAllCmdArgsOptions("thanks", $author);

      $thanks = null;

      $name = $author;

      if (!empty($thanks_arg)) {
        $thanks = $thanks_arg[0]->value;
        $name = $thanks_arg[0]->type === 'arg' ? str_replace('\thanks{' . $thanks . '}', ' ', $author) : $author;
      }

      $authors[] = (object) ['name' => trim($name), 'thanks' => $thanks ?? ''];
    }

    return $authors;
  }

  public function getNumberWithin(): array
  {
    $pattern = '/(\\\numberwithin)/';

    preg_match_all($pattern, $this->src, $matches, PREG_OFFSET_CAPTURE);

    if (!isset($matches[1])) return [];

    $number_within = [];

    foreach ($matches[1] as $match) {
      $offset = (int) $match[1];

      $args = StrHelper::getAllCmdArgsOptions('numberwithin', substr($this->src, $offset));

      $number_within[] = $args;
    }

    return $number_within;
  }
}
