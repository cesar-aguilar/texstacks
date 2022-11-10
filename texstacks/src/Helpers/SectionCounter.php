<?php

namespace TexStacks\Helpers;

class SectionCounter {
  
  private $counters = [];

  /**
   *
   */
  public function __construct($doc_class) {

    if ($doc_class === 'book' || $doc_class === 'report')
      $this->counters['chapter'] = 0;

    $this->counters = array_merge($this->counters, [
      'section' => 0,
      'subsection' => 0,
      'subsubsection' => 0,
      'paragraph' => 0,
      'subparagraph' => 0
    ]);

  }

  /**
   *
   */
  public function reset() {
    $this->counters = array_map(fn($x) => 0, $this->counters);
  }

  /**
   *
   */
  public function get($section_name, $increment = true): string
  {
    if (str_contains($section_name, '*')) return '';

    if (str_contains($section_name, 'paragraph')) return '';

    if (!key_exists($section_name, $this->counters)) return '';

    $this->counters[$section_name] += $increment ? 1 : 0;

    $section_numbers = [];

    foreach ($this->counters as $key => $value) {

      if ($value) $section_numbers[] = $value;

      if ($key == $section_name) break;
    }

    if ($increment) $this->reset_Children($section_name);

    return implode('.', $section_numbers);
  }

  /**
   *
   */
  public function isCounter($name): bool
  {
    return key_exists($name, $this->counters);
  }

  /**
   *
   */
  private function reset_children($section_name = null): void
  {
  
    $flag = false;

    foreach ($this->counters as $key => $value) {
      if ($flag) $this->counters[$key] = 0;

      if ($key === $section_name) $flag = true;
    }

  }
  

}