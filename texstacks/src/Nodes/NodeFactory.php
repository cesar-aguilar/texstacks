<?php

namespace TexStacks\Nodes;

use TexStacks\Commands\Core\DisplayMathEnv;

class NodeFactory {

  public static function getNode($id, $token) {

    $args = ['id' => $id, ...(array) $token];

    if ($token->type === 'cmd:section') {
      return new SectionNode($args);
    } else if ($token->type === 'environment:theorem') {
      return new TheoremNode($args);
    } else if ($token->type === 'environment:displaymath') {
      return new DisplayMathNode($args);
    } else if (preg_match('/environment/', $token->type)) {
      return new EnvironmentNode($args);
    } else if ($token->type === 'cmd:symbol') {
      return new Node($args);
    } else {
      return new CommandNode($args);
    }
  }

}