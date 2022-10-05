<?php

namespace TexStacks\Parsers;

use SplFileObject;
use TexStacks\Helpers\StrHelper;

class AuxParser
{

  private $aux_file;
  private $labels = [];

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

  private function parse()
  {
    while (!$this->aux_file->eof()) {
      $line = $this->aux_file->fgets();
      $line = trim($line);
      if (strpos($line, '\newlabel') !== false) {
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
}
