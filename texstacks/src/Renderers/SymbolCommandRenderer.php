<?php

namespace TexStacks\Renderers;

use TexStacks\Nodes\CommandNode;

class SymbolCommandRenderer
{
  public static function renderNode(CommandNode $node, string $body = null): string
  {

    $options = $node->commandOptions() ? "[" . $node->commandOptions() . "]" : '';

    $src = "\\" . $node->body . $options;

    if ($node->ancestorOfType(['environment:displaymath', 'environment:verbatim', 'inlinemath'])) {
      // if ($node instanceof Node) return $src;
      return $src;
      // return $node->commandSource();
    }

    return match ($node->body) {
      '$' => '&#36;',
      '%' => '%',
      '&' => '&',
      '#' => '#',
      '_' => '_',
      '-' => '',
      '{' => '{',
      '}' => '}',
      "\\" => '<br>',
      "/" => ' ',
      ' ' => '&nbsp;',
      ',' => ' ',
      'S' => '&sect;',
      'P' => '&para;',
      'pounds' => '&pound;',
      'dag' => '&dagger;',
      'ddag' => '&Dagger;',
      'copyright' => '&copy;',
      'textbackslash' => '<span>&bsol;<span>',
      'texttimes' => '&times;',
      'textdiv' => '&divide;',
      'textsection' => '&sect;',
      default => $src
      };
    }
  }
  