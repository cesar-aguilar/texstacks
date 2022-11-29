<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\BaseLexer;

class FrontMatterLexer extends BaseLexer
{

  public function __construct($data = [])
  {
    parent::__construct($data);

    $this->registerCommandGroup([
      \TexStacks\Commands\AmsArt\AmsArtOneArgPreOptions::class,
      \TexStacks\Commands\AmsArt\AmsArtOneArg::class,
    ]);

  }

  protected function postProcessTokens(): void
  {

    $this->tokens = array_filter($this->tokens, function ($token) {
      
      return in_array($token->type, [
        \TexStacks\Commands\AmsArt\AmsArtOneArgPreOptions::type(),
        \TexStacks\Commands\AmsArt\AmsArtOneArg::type(),
      ]);

    });

  }
}
