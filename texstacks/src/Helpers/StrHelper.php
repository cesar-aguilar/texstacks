<?php

namespace TexStacks\Helpers;

class StrHelper
{

  /**
   * Extracts a string between two other strings
   * 
   * If $haystack = stuff + $start + $content + $end + stuff 
   * returns $start + $content + $end
   */
  public static function PluckIncludeDelimiters(string $start, string $end, string $haystack): string
  {
    $start_pos = strpos($haystack, $start);

    if ($start_pos === false) return $haystack;

    $end_pos = strpos($haystack, $end);

    if ($end_pos === false) return $haystack;

    return trim(substr($haystack, $start_pos, $end_pos - $start_pos + strlen($end)));
  }

  /**
   * Extracts a string between two other strings
   * 
   * If $haystack = stuff + $start + $content + $end + stuff
   * then returns $content
   */
  public static function PluckExcludeDelimiters(string $start, string $end, string $haystack): string
  {
    $start_pos = strpos($haystack, $start);

    if ($start_pos === false) return $haystack;

    $end_pos = strpos($haystack, $end);

    if ($end_pos === false) return $haystack;

    return trim(substr($haystack, $start_pos + strlen($start), $end_pos - $start_pos - strlen($start)));
  }

  /**
   * Deletes a string from $start to $end
   * 
   * If $haystack = $content_a + $start + $content + $end + $content_b
   * then returns $content_a + $content_b
   */
  public static function DeleteFromTo(string $start, string $end, string $haystack): string
  {
    $start_pos = strpos($haystack, $start);

    if ($start_pos === false) return $haystack;

    $end_pos = strpos($haystack, $end, $start_pos);

    if ($end_pos === false) return $haystack;

    $len = $end_pos - $start_pos;

    return trim(substr_replace($haystack, '', $start_pos, $len));
  }


  public static function DeleteLatexComments(string $haystack, string $replace=null): string
  {

    $replace = $replace ?? "\n";

    $output = preg_replace('/(?<!\\\)%.*\n/', $replace, $haystack);

    return str_replace('\%', '%', $output);
  }


  public static function findAllPositions(string $needle, string $haystack): array
  {
    $positions = [];
    $offset = 0;
    while (($pos = strpos($haystack, $needle, $offset)) !== false) {
      $positions[] = $pos;
      $offset = $pos + 1;
    }
    return $positions;
  }

  public static function Slugify($title, $separator = '-')
  {

    // Convert all dashes/underscores into separator
    $flip = $separator === '-' ? '_' : '-';

    $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);

    // Replace @ with the word 'at'
    $title = str_replace('@', $separator . 'at' . $separator, $title);

    // Remove all characters that are not the separator, letters, numbers, or whitespace.
    $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', mb_strtolower($title));

    // Replace all separator characters and whitespace by a single separator
    $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);

    return trim($title, $separator);
  }

  public static function findStringLineNumber(string $needle, string $haystack): int
  {
    $lines = explode("\n", $haystack);
    $line_number = 0;
    foreach ($lines as $line) {
      $line_number++;
      if (strpos($line, $needle) !== false) {
        return $line_number;
      }
    }
    return -1;
  }

  public static function addLineNumbers(string $haystack, int $offset=0): string
  {
    $lines = explode("\n", $haystack);
    $line_number = $offset;
    $output = '';
    foreach ($lines as $line) {
      $output .= $line_number . ' - ' . $line . "\n";
      $line_number++;
    }
    return $output;
  }

  /**
   * Example of $delimiters:
   *
   * $delimiters = ['{' => '}'];
   * $delimiters = ['{' => '}', '[' => ']'];
   * $delimiters = ['<' => '>', '[' => ']'];
   *
   */
  public static function getAllCmdArgsOptions($command, $line, $delimiters=null)
  {

    $delimiters = $delimiters ?? ['{' => '}', '[' => ']'];

    $left_delimiters = array_keys($delimiters);

    $cursor = 0;
    $length = strlen($line);

    $cursor = strpos($line, "\\" . $command);

    if ($cursor === false) return [];

    // Move cursor one position after the command
    $cursor += strlen($command) + 1;

    $args = [];

    while ($cursor < $length) {

      $char = $line[$cursor];

      if (in_array($char, [" ", "\n", "\t"] )) {
        $cursor++;
        continue;
      }

      if ($char === '%') {
        while ($cursor < $length && $line[$cursor] !== "\n") {
          $cursor++;
        }
        continue;
      }

      if (!in_array($char, $left_delimiters)) return $args;

      $left_delim = $char;
      $right_delim = $delimiters[$char];

      $cursor++;

      $char = $cursor < $length ? $line[$cursor] : null;

      $delim_count = 1;

      $current_arg = '';

      while (!is_null($char))
      {
  
        if ($char !== $right_delim)
        {

          $current_arg .= $char;
          
          if ($char === $left_delim) $delim_count++;

        }
        else
        {

          $delim_count--;

          if ($delim_count === 0) {
            // $args[] = [$left_delim . $right_delim, $current_arg];
            $args[] = (object) ['type' => $left_delim.$right_delim === '{}' ? 'arg' : 'option', 'value' => $current_arg];
            $cursor++;
            break;
          }
          
          $current_arg .= $right_delim;

        }

        $cursor++;

        $char = $cursor < $length ? $line[$cursor] : null;
  
      }
             
    }

    return $args;

  }

  public static function getCmdArg(string $command, string $text) : string
  {
    $arg = self::getAllCmdArgsOptions($command, $text, delimiters: ['{' => '}']);

    if (empty($arg)) return '';

    return self::cleanUpText($arg[0]->type === 'arg' ? $arg[0]->value : '');

  }

  public static function consolidateWhiteSpace($text) {
    return preg_replace('/\s+/', ' ', $text);
  }

  public static function cleanUpText($text)
  {
    $text = str_replace("\n", ' ', $text);
    $text = preg_replace('/(\\\)(\\\)\[(.*?)\]/', '<br>', $text);
    return trim(self::consolidateWhiteSpace($text));

  }

  public static function isAlpha($string)
  {
    return ctype_alpha(str_replace(' ', '', $string));
  }

  public static function isNotAlpha($string) {
    return !self::isAlpha($string);
  }

}