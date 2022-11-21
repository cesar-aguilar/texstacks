<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\BaseLexer;

class ArticleLexer extends BaseLexer
{

  public function __construct($data = [])
  {
    parent::__construct($data);

    if (isset($data['thm_env'])) {
      \TexStacks\Commands\TheoremEnv::add($data['thm_env']);
    }

    if (isset($data['citations'])) {
      \TexStacks\Commands\Core\Citations::add($data['citations']);
    }

    if (isset($data['ref_labels'])) {
      \TexStacks\Commands\Core\ReferenceCommands::add($data['ref_labels']);
    }

    $this->registerCommandGroup([
      \TexStacks\Commands\Core\SectionCommands::class,
      \TexStacks\Commands\Core\FontCommands::class,
      \TexStacks\Commands\Core\FontEnv::class,
      // FontDeclarations must come after FontEnv
      \TexStacks\Commands\Core\FontDeclarations::class,
      \TexStacks\Commands\Core\Citations::class,
      \TexStacks\Commands\Core\ReferenceCommands::class,
      \TexStacks\Commands\Core\AlphaSymbol::class,
      \TexStacks\Commands\Core\DisplayMathEnv::class,
      \TexStacks\Commands\Core\InlineMathEnv::class,
      \TexStacks\Commands\Core\ListEnv::class,
      \TexStacks\Commands\Core\TabularEnv::class,
      \TexStacks\Commands\Core\SpacingCommands::class,
      \TexStacks\Commands\Core\SpaceCommands::class,
      \TexStacks\Commands\Core\ActionCommands::class,
      \TexStacks\Commands\TheoremEnv::class,
      \TexStacks\Commands\GenericEnv::class,
      \TexStacks\Commands\CommandWithOptions::class,
      \TexStacks\Commands\OneArgPreOptions::class,
      \TexStacks\Commands\OneArg::class,
      \TexStacks\Commands\EnvWithArg::class,
      \TexStacks\Commands\TwoArgs::class,
    ]);

    $this->registerDefaultEnvironment(
      \TexStacks\Commands\Environment::class
    );
  }

  protected function postProcessTokens(): void
  {

    $this->tokens = array_filter($this->tokens, function ($token) {
      return
        $token->type === \TexStacks\Commands\TheoremEnv::type() ||
        $token->type === \TexStacks\Commands\Core\SectionCommands::type() ||
        $token->type === \TexStacks\Commands\Core\InlineMathEnv::type() ||
        $token->type === 'text';
    });
  }
}
