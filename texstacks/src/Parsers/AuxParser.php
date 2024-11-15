<?php

namespace TexStacks\Parsers;

use TexStacks\Helpers\StrHelper;

class AuxParser
{

  private $aux_src;
  private $labels = [];
  private $sections = [];
  private $citations = [];

  public function __construct($aux_src = '')
  {

    $this->aux_src = $aux_src;

    $this->parse();
  }

  public function getLabelsAsArray(): array
  {
    return $this->labels;
  }

  public function getSectionsAsArray(): array
  {
    return $this->sections;
  }

  public function getCitationsAsArray(): array
  {
    return $this->citations;
  }

  private function parse()
  {

    $lines = explode(PHP_EOL, $this->aux_src);

    foreach ($lines as $line) {

      $line = trim($line);

      if (str_contains($line, '\newlabel')) {
        $this->parseNewLabel($line);
      } else if (str_contains($line, '\bibcite')) {
        $this->parseBibCite($line);
      }

    }

  }

  /**
   * Parses a line containing a \newlabel command
   * e.g., \newlabel{<label>}{{currentlabel}{thepage}{labelname}{href}{}}
   * and adds to the labels array
   */
  private function parseNewLabel($line)
  {

    $args = StrHelper::getAllCmdArgsOptions('newlabel', $line);

    if (count($args) !== 2) return;

    $label = $args[0]?->type === 'arg' ? $args[0]?->value : '';

    $fake_cmd = '\fake' . $args[1]->value;

    $ref_num_args = StrHelper::getAllCmdArgsOptions('fake', $fake_cmd);

    if (count($ref_num_args) < 2) return;

    $reference_number = trim($ref_num_args[0]->value, characters: "{}");

    $reference_number = preg_replace('/(?<!\\\)\$(.*?)\$/s', "\\( $1 \\)", $reference_number);

    if ($label && $reference_number) $this->labels[$label] = $reference_number;
  }

  /**
   * Parses a line containing a \bibcite command
   * e.g., \bibcite{DG:98}{3}, and adds to the citations array
   * 
   * @param string $line
   */
  private function parseBibCite($line)
  {
    $args = StrHelper::getAllCmdArgsOptions('bibcite', $line);

    if (count($args) !== 2) return;

    if ($args[0]->type === 'arg' && $args[1]->type === 'arg') {
      $this->citations[$args[0]->value] = $args[1]->value;
    }
  }

  private function parseContentsLine($line)
  {
    $args = StrHelper::getAllCmdArgsOptions('contentsline', $line);

    if (!isset($args[0])) return;

    $type = $args[0];

    if (!isset($args[1])) return;

    $toc_entry = $args[1];

    $number = StrHelper::getAllCmdArgsOptions('numberline', $toc_entry);

    $ref_num = $number[0] ?? null;

    $toc_entry = str_replace('{' . $ref_num . '}', '', preg_replace('/\\\numberline\s*/', '', $toc_entry));

    $this->sections[] = [
      'type' => $type,
      'ref_num' => $ref_num,
      'toc_entry' => $toc_entry
    ];
  }
}
