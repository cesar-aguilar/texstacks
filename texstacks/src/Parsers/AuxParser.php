<?php

namespace TexStacks\Parsers;

use SplFileObject;
use TexStacks\Helpers\StrHelper;

class AuxParser
{

  private $aux_file;
  private $labels = [];
  private $sections = [];

  public function __construct(private $absolute_path)
  {
    if (!file_exists($absolute_path)) {
      // throw new \Exception("File not found: $absolute_path");
      return;
    }

    try {
      $this->aux_file = new SplFileObject($absolute_path);
    } catch (\Exception $e) {
      throw new \Exception("Error reading file: $absolute_path");
    }

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

  private function parse()
  {
    while (!$this->aux_file->eof())
    {
      $line = $this->aux_file->fgets();
      $line = trim($line);
      if (str_contains($line, '\newlabel')) {
        $this->parseNewLabel($line);
      }
    }
  }

  private function parseNewLabel($line)
  {
    $line = str_replace('\newlabel', '', $line);

    $label = StrHelper::PluckExcludeDelimiters('{', '}', $line);

    $ref_num_component = StrHelper::PluckIncludeDelimiters('{{', '}}', $line);

    $reference_number = StrHelper::PluckExcludeDelimiters('{{', '}{', $ref_num_component);

    $this->labels[$label] = $reference_number;
  }

  private function parseContentsLine($line)
  {
    $args = StrHelper::getAllCmdArgs('contentsline', $line);
          
    if (!isset($args[0])) return;

    $type = $args[0];

    if (!isset($args[1])) return;

    $toc_entry = $args[1];
    
    $number = StrHelper::getAllCmdArgs('numberline', $toc_entry);
    
    $ref_num = $number[0] ?? null;

    $toc_entry = str_replace('{' . $ref_num . '}', '', preg_replace('/\\\numberline\s*/', '', $toc_entry));

    $this->sections[] = [
      'type' => $type,
      'ref_num' => $ref_num,
      'toc_entry' => $toc_entry
    ];

  }

}
