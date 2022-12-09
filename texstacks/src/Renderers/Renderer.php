<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\Node;
use TexStacks\Renderers\CaptionRenderer;
use TexStacks\Renderers\EnvironmentRenderer;
use TexStacks\Renderers\SectionCommandRenderer;
use TexStacks\Renderers\ThmEnvironmentRenderer;
use TexStacks\Renderers\ListEnvironmentRenderer;
use TexStacks\Renderers\GroupEnvironmentRenderer;
use TexStacks\Renderers\TabularEnvironmentRenderer;
use TexStacks\Renderers\DisplayMathEnvironmentRenderer;

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

    if ($node->hasType('root')) return self::renderRoot($node, $body);

    if ($node->hasType('cmd:section')) return SectionCommandRenderer::renderNode($node, $body);

    if ($node->hasType('environment:displaymath')) return DisplayMathEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('inlinemath')) return "\\(" . $body . "\\)";

    if ($node->hasType('environment:theorem')) return ThmEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('environment:group')) return GroupEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType(['environment:default', 'environment:generic'])) return EnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('environment:tabular')) return TabularEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('environment:list')) return ListEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('environment:bibliography')) return BibliographyEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('cmd:font')) return FontCommandRenderer::renderNode($node, $body);

    if ($node->hasType(['cmd:symbol', 'cmd:alpha-symbol'])) return SymbolCommandRenderer::renderNode($node, $body);

    if ($node->hasType('cmd:caption')) return CaptionRenderer::renderNode($node, $body);

    if ($node->hasType('cmd:two-args')) return self::renderTwoArgsCommand($node, $body);

    if ($node->hasType('environment:verbatim')) return "<pre>$body</pre>";

    if ($node->hasType('cmd:spacing')) return self::renderSpacingCommand($node, $body);

    if ($node->hasType('cmd:item')) return self::renderItemNode($node, $body);

    if ($node->hasType('cmd:bibitem')) return self::renderBibItemNode($node, $body);

    if ($node->hasType('cmd:includegraphics')) return self::renderIncludeGraphics($node, $body);

    if ($node->hasType('cmd:label')) return $node->commandSource();

    if ($node->hasType('cmd:ref')) return self::renderRef($node, $body);

    if ($node->hasType('cmd:cite')) return self::renderCitations($node, $body);

    if ($node->hasType('cmd:footnote')) return self::renderFootnote($node, $body);

    // if ($node->hasType('cmd:frontmatter')) return self::renderFrontMatter($node, $body);

    if ($node->hasType('tag')) return "\\tag{" . $body . "}";

    if ($node->hasType('cmd:font-declaration')) return self::renderFontDeclaration($node);

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

  private static function renderRoot($node, $body)
  {

    $body = preg_replace('/^(<br>)+|(<br>)+$/', '', $body);

    if ($node->hasClasses()) {
      $classes = $node->getClasses();
      return "<span class='$classes'>$body</span>";
    }
    return $body;
  }

  private static function renderItemNode($node, $body)
  {
    if ($node->ancestorOfType('environment:verbatim')) return $node->commandSource() . ' ' . $body;

    if ($node->parent()?->commandContent() === 'description') {
      $label = $node->commandOptions();
      return "<dt>$label</dt><dd>$body</dd>";
    }

    $body = trim($body);

    return "<li>$body</li>";
  }

  private static function renderBibItemNode($node, $body)
  {
    if ($node->ancestorOfType('environment:verbatim')) return $node->commandSource() . ' ' . $body;

    $body = str_replace(['<br>', '\newblock'], '', $body);

    $body = trim($body);

    return "<li id=\"{$node->commandContent()}\">$body</li>";
  }

  private static function renderFontDeclaration($node)
  {
    if ($node->ancestorOfType('environment:verbatim')) return "\\" . $node->body;

    return '';
  }

  private static function renderIncludeGraphics($node, string $body = null): string
  {

    if ($node->ancestorOfType('environment:verbatim')) return $node->commandSource();

    return "<img src=\"{$node->commandContent()}\" alt=\"{$node->commandContent()}\" />";
  }

  private static function renderCitations($node, string $body = null): string
  {

    if ($node->commandOptions() instanceof Node) {
      $node->setOptions(Renderer::render($node->commandOptions()));
    }

    $options = $node->commandOptions() != '' ? ", " . $node->commandOptions() : null;

    $ids = array_map(trim(...), explode(',', $node->commandContent()));

    $nums = explode(',', $node->body);

    foreach (array_combine($ids, $nums) as $id => $num) {
      $a[] = "<a href=\"#$id\">$num</a>";
    }

    $value = implode(', ', $a);

    return " [$value$options]";
  }

  private static function renderFootnote($node, string $body = null): string
  {
    if ($node->ancestorOfType(['environment:displaymath', 'inlinemath', 'environment:verbatim'])) return $node->commandSource();

    $body = $node->commandContent();

    if ($node->commandContent() instanceof Node) {
      $body = trim(Renderer::render($node->commandContent()));
    }

    $num = chr((int)$node->commandRefNum() + 96);

    $html = "<details class=\"footnote\"><summary class=\"footnote\">[$num]</summary><p class=\"footnote-content\">$body</p></details>";

    return $html;
  }

  // private static function renderFrontMatter($node, string $body = null): string
  // {
  //   if ($node->ancestorOfType('environment:verbatim'))
  //     return $node->commandSource();

  //   $body = $node->commandContent();

  //   if ($node->commandContent() instanceof Node) {
  //     $body = trim(Renderer::render($node->commandContent()));
  //   }

  //   $html = $node->commandSource();

  //   if ($node->commandName() === 'thanks') {
  //     $html = "<details class=\"footnote\"><summary class=\"footnote\">&#x2731;</summary><p class=\"footnote-content\">$body</p></details>";
  //   }

  //   return $html;

  // }

  private static function renderRef($node, string $body = null): string
  {
    if ($node->commandName() === 'ref') {
      return "<a href='#{$node->commandContent()}'>{$node->commandOptions()}</a>";
    }

    return "(<a style=\"margin:0 0.1rem;\" href='#{$node->commandContent()}'>{$node->commandOptions()}</a>)";
  }

  private static function renderSpacingCommand($node, string $body = null): string
  {
    if ($node->ancestorOfType(['environment:verbatim', 'environment:displaymath', 'inlinemath'])) return $node->commandSource() . ' ';

    return match ($node->commandName()) {
      'smallskip' => '<div style="height: 1em;"></div>',
      'medskip' => '<div style="height: 2em;"></div>',
      'bigskip' => '<div style="height: 3em;"></div>',
      default => '',
    };
  }

  private static function renderTwoArgsCommand($node, string $body = null): string
  {
    if ($node->ancestorOfType(['environment:verbatim', 'environment:displaymath', 'inlinemath'])) return $node->commandSource();

    if ($node->commandName() === 'texorpdfstring') {
      return Renderer::render($node->commandContent());
    }

    return $node->commandSource();
  }
}
