<?php

namespace TexStacks\Parsers;

use TexStacks\Helpers\StrHelper;

class LatexParser
{

  /**
   * Parse a LaTeX command with label: \command{content}\label{label}
   * 
   */
  public static function parseCommandAndLabel(string $name, string $str): array
  {

    $label = preg_match('/\\\\label\{(?<label>[^}]*)\}/', $str, $match) ? $match['label'] : null;

    $without_label = preg_replace('/\\\\label\{([^}]*)\}/', '', $str);

    $content = preg_match('/\\\\' . $name . '\{(?<content>[^}]*)\}/', $without_label, $match) ? $match['content'] : null;

    return ['content' => $content, 'label' => $label];
  }

  public static function parseCommandOptionsAndLabel(string $name, string $str): array
  {

    $label = preg_match('/\\\\label\{(?<label>[^}]*)\}/', $str, $match) ? $match['label'] : null;

    $without_label = preg_replace('/\\\\label\{[^}]*\}/', '', $str);

    $options = preg_match('/\[(?<options>[^}]*)\]/', $without_label, $match) ? $match['options'] : null;

    $without_options = preg_replace('/\[[^}]*\]/', '', $without_label);

    $content = preg_match('/\\\\' . $name . '\{(?<content>[^}]*)\}/', $without_options, $match) ? $match['content'] : null;

    return ['content' => $content, 'label' => $label, 'options' => $options];
  }

  
  public static function normalizeLatexSource(string $latex_src): string
  {
    $html_src = StrHelper::PluckIncludeDelimiters('\begin{document}', '\end{document}', $latex_src);
    $html_src = str_replace('\begin{document}', "\n", $html_src);
    $html_src = str_replace('\end{document}', "\n", $html_src);

    $html_src = StrHelper::DeleteLatexComments($html_src);

    // Replace less than and greater than symbols with latex commands
    $html_src = str_replace('<', ' \lt ', $html_src);
    $html_src = str_replace('>', ' \gt ', $html_src);

    // Replace $$...$$ with \[...\] and then $...$ with \(...\)
    $html_src = preg_replace('/\$\$(.*?)\$\$/s', '\\[$1\\]', $html_src);
    $html_src = preg_replace('/\$(.+?)\$/s', '\\($1\\)', $html_src);

    return self::commandsOnNewLine($html_src);
  }

  /**
   * Put each LaTeX command on a new line
   * 
   * @param string $latex_src
   * @return string
   */
  private static function commandsOnNewLine(string $latex_src): string
  {
    $commands = ['begin', 'end', 'chapter', 'section', 'subsection', 'subsubsection'];

    foreach ($commands as $command) {
      $latex_src = preg_replace('/^(.+)\\\\' . $command . '\{(.*?)\}/m', "$1\n\\$command{" . "$2" . "}", $latex_src);
    }

    return $latex_src;
  }
}
