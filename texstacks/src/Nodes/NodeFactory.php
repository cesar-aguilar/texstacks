<?php

namespace TexStacks\Nodes;

class NodeFactory {

  public static function createRootNode() {
    return new RootNode([
      'id' => 0,
      'type' => 'root',
    ]);
  }

  public static function getNode($id, $token) {

    $args = ['id' => $id, ...(array) $token];

    if ($token->type === 'inlinemath') {
      return new InlineMathNode($args);
    } else if ($token->type === 'environment:group') {
      return new GroupNode($args);
    } else if ($token->type === 'environment:displaymath') {
      return new DisplayMathNode($args);
    } else if ($token->type === 'cmd:section') {
      return new SectionNode($args);
    } else if ($token->type === 'environment:theorem') {
      return new TheoremNode($args);
    } else if ($token->type === 'environment:list') {
      return new ListNode($args);
    } else if ($token->type === 'cmd:font') {
      return new FontCommandNode($args);
    } else if ($token->type === 'cmd:symbol' || $token->type === 'cmd:alpha-symbol') {
      return new SymbolNode($args);
    } else if ($token->type === 'cmd:caption') {
      return new CaptionNode($args);
    } else if ($token->type === 'environment:tabular') {
      return new TabularNode($args);
    } else if ($token->type === 'environment:bibliography') {
      return new BibliographyNode($args);
    } else if ($token->type === 'cmd:label') {
      return new LabelNode($args);
    } else if ($token->type === 'cmd:ref') {
      return new ReferenceNode($args);
    } else if ($token->type === 'cmd:cite') {
      return new CitationNode($args);
    } else if ($token->type === 'cmd:item') {
      return new ItemNode($args);
    } else if ($token->type === 'cmd:bibitem') {
      return new BibItemNode($args);
    } else if ($token->type === 'cmd:spacing') {
      return new SpacingCommandNode($args);
    } else if ($token->type === 'cmd:footnote') {
      return new FootnoteNode($args);
    } else if ($token->type === 'cmd:font-declaration') {
      return new FontDeclarationNode($args);
    } else if ($token->type === 'cmd:includegraphics') {
      return new IncludeGraphicsNode($args);
    } else if (preg_match('/environment:/', $token->type)) {
      return new EnvironmentNode($args);
    } else if ($token->type === 'cmd:two-args') {
      return new TwoArgsNode($args);
    } else {
      return new CommandNode($args);
    }
  }

}