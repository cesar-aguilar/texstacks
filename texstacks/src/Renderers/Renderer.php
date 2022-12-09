<?php

namespace TexStacks\Renderers;

class Renderer
{
  public static function render($root): string
  {
    return self::renderRecursively($root);
  }

  private static function renderRecursively($node): string
  {
    if ($node->isLeaf()) {
      return self::renderNode($node, $node->body);
    }

    return self::renderNode(
      $node,
      implode('', array_map(fn ($child) => self::renderRecursively($child), $node->children()))
    );
  }

  private static function renderNode($node, string $body = null): string
  {

    $body = $body ?? '';

    if ($node->hasType('root')) return $node->render($body);

    if ($node->hasType('cmd:section')) return $node->render($body);

    if ($node->hasType('environment:displaymath')) return $node->render($body);

    if ($node->hasType('inlinemath')) return $node->render($body);

    if ($node->hasType('environment:theorem')) return $node->render($body);

    if ($node->hasType('environment:group')) return $node->render($body);

    if ($node->hasType(['environment:default', 'environment:generic', 'environment:verbatim'])) return $node->render($body);

    if ($node->hasType('environment:tabular')) return $node->render($body);

    if ($node->hasType('environment:list')) return $node->render($body);

    if ($node->hasType('environment:bibliography')) return $node->render($body);

    if ($node->hasType('cmd:font')) return $node->render($body);

    if ($node->hasType(['cmd:symbol', 'cmd:alpha-symbol'])) return $node->render($body);

    if ($node->hasType('cmd:caption')) return $node->render($body);

    if ($node->hasType('cmd:two-args')) return $node->render($body);

    if ($node->hasType('cmd:spacing')) return $node->render($body);

    if ($node->hasType('cmd:item')) return $node->render($body);

    if ($node->hasType('cmd:bibitem')) return $node->render($body);

    if ($node->hasType('cmd:includegraphics')) $node->render($body);

    if ($node->hasType('cmd:label')) return $node->render($body);

    if ($node->hasType('cmd:ref')) return $node->render($body);

    if ($node->hasType('cmd:cite')) return $node->render($body);

    if ($node->hasType('cmd:footnote')) return $node->render($body);

    if ($node->hasType('tag')) return "\\tag{" . $body . "}";

    if ($node->hasType('cmd:font-declaration')) return $node->render($body);

    if ($node->hasType('cmd:accent')) return $node->body;

    if ($node->ancestorOfType(['environment:displaymath', 'inlinemath', 'environment:tabular', 'environment:verbatim'])) return $body;

    // Remove vertical spacing of the type \\[1em] since not in tabular-like environment
    // $output = preg_replace('/(\\\)(\\\)\[(.*?)\]/', '<br>', $body);

    // return preg_replace('/\n{2,}/', "<br><br>", $body);

    if ($body === "\n") return '';

    // return preg_replace('/(\n[\s\t]*){2,}/', "<br><br>", $body);
    return $body;

    // Replace two \n characters with <br>
    // return str_replace("\n\n", '<br><br>', $output);

    // Remove double backslashes (the node is text and should not be in math or tabular environment)
    // return preg_replace('/(\\\)(\\\)/', '<br>', $output);
  }

}
