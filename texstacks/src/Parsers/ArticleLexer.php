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
      \TexStacks\Commands\Core\LabelCommand::class,
      \TexStacks\Commands\Core\Citations::class,
      \TexStacks\Commands\Core\ReferenceCommands::class,
      \TexStacks\Commands\Core\AlphaSymbol::class,
      \TexStacks\Commands\Core\DisplayMathEnv::class,
      \TexStacks\Commands\Core\InlineMathEnv::class,
      \TexStacks\Commands\Core\ListEnv::class,
      \TexStacks\Commands\Core\ItemCommand::class,
      \TexStacks\Commands\Core\TabularEnv::class,
      \TexStacks\Commands\Core\SpacingCommands::class,
      \TexStacks\Commands\Core\SpaceCommands::class,
      \TexStacks\Commands\Core\ActionCommands::class,
      \TexStacks\Commands\Core\BibliographyEnv::class,
      \TexStacks\Commands\Core\BibItemCommand::class,
      \TexStacks\Commands\Core\CaptionCommand::class,
      \TexStacks\Commands\Core\IncludeGraphicsCommand::class,
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
      return in_array(
        $token->type,
        [
          // \TexStacks\Commands\TheoremEnv::type(),
          // \TexStacks\Commands\Core\LabelCommand::type(),
          // \TexStacks\Commands\Core\ReferenceCommands::type(),
          \TexStacks\Commands\Core\SectionCommands::type(),
          // \TexStacks\Commands\Core\InlineMathEnv::type(),
          // \TexStacks\Commands\Core\DisplayMathEnv::type(),
          // \TexStacks\Commands\Core\BibliographyEnv::type(),
          // \TexStacks\Commands\Core\ListEnv::type(),
          // \TexStacks\Commands\Core\ItemCommand::type(),
          // \TexStacks\Commands\Core\BibItemCommand::type(),
          // \TexStacks\Commands\Core\CaptionCommand::type(),
          // \TexStacks\Commands\Core\IncludeGraphicsCommand::type(),
          // \TexStacks\Commands\OneArg::type(),
          // 'environment:group',
          // 'text',
        ]
      );
    });
  }
}
