<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\BaseLexer;

class ArticleLexer extends BaseLexer
{

  public function __construct($data = [])
  {
    parent::__construct($data);

    $this->addUpdatableCommand(\TexStacks\Commands\Core\NewTheorem::list());

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
      \TexStacks\Commands\Core\PreambleCommands::class,
      \TexStacks\Commands\Core\NewCommands::class,
      \TexStacks\Commands\Core\NewEnvironments::class,
      \TexStacks\Commands\Core\NewTheorem::class,
      \TexStacks\Commands\Core\SectionCommands::class,
      \TexStacks\Commands\Core\FontCommands::class,
      \TexStacks\Commands\Core\FontEnv::class,
      // FontDeclarations must come after FontEnv
      \TexStacks\Commands\Core\FontDeclarations::class,
      \TexStacks\Commands\Core\LabelCommand::class,
      \TexStacks\Commands\Core\Citations::class,
      \TexStacks\Commands\Core\Footnote::class,
      \TexStacks\Commands\Core\ReferenceCommands::class,
      \TexStacks\Commands\Core\AlphaSymbol::class,
      \TexStacks\Commands\Core\DisplayMathEnv::class,
      \TexStacks\Commands\Core\InlineMathEnv::class,
      \TexStacks\Commands\Core\ListEnv::class,
      \TexStacks\Commands\Core\ItemCommand::class,
      \TexStacks\Commands\Core\TabularEnv::class,
      \TexStacks\Commands\Core\VerbatimEnv::class,
      \TexStacks\Commands\Core\SpacingCommands::class,
      \TexStacks\Commands\Core\SpaceCommands::class,
      \TexStacks\Commands\Core\ActionCommands::class,
      \TexStacks\Commands\Core\BibliographyEnv::class,
      \TexStacks\Commands\Core\BibItemCommand::class,
      \TexStacks\Commands\Core\CaptionCommand::class,
      \TexStacks\Commands\Core\IncludeGraphicsCommand::class,
      // \TexStacks\Commands\Core\AccentCommand::class,
      // \TexStacks\Commands\Core\SymbolCommand::class,
      \TexStacks\Commands\TheoremEnv::class,
      \TexStacks\Commands\Environment::class,
      \TexStacks\Commands\GenericEnv::class,
      // \TexStacks\Commands\CommandWithOptions::class,
      // \TexStacks\Commands\OneArgPreOptions::class,
      \TexStacks\Commands\OneArg::class,
      // \TexStacks\Commands\EnvWithArg::class,
      \TexStacks\Commands\TwoArgs::class,
      \TexStacks\Commands\AmsArt\AmsArtOneArg::class,
      \TexStacks\Commands\AmsArt\AmsArtOneArgPreOptions::class,
    ]);
  }

  protected function postProcessTokens(): void
  {

    parent::postProcessTokens();

    // $this->tokens = array_filter($this->tokens, function ($token) {
    //   return in_array(
    //     $token->type,
    //     [
    //       // \TexStacks\Commands\AmsArt\AmsArtOneArg::type(),
    //       // \TexStacks\Commands\AmsArt\AmsArtOneArgPreOptions::type(),
    //       // \TexStacks\Commands\Core\SymbolCommand::type(),
    //       // \TexStacks\Commands\Core\ReferenceCommands::type(),
    //       // \TexStacks\Commands\Core\PreambleCommands::type(),
    //       // \TexStacks\Commands\Core\SectionCommands::type(),
    //       // \TexStacks\Commands\Core\InlineMathEnv::type(),
    //       // \TexStacks\Commands\Core\DisplayMathEnv::type(),
    //       // \TexStacks\Commands\Core\BibliographyEnv::type(),
    //       // \TexStacks\Commands\Core\ListEnv::type(),
    //       // \TexStacks\Commands\Core\ItemCommand::type(),
    //       // \TexStacks\Commands\Core\BibItemCommand::type(),
    //       // \TexStacks\Commands\Core\CaptionCommand::type(),
    //       // \TexStacks\Commands\Core\IncludeGraphicsCommand::type(),
    //       // \TexStacks\Commands\OneArg::type(),
    //       // \TexStacks\Commands\TwoArgs::type(),
    //       // 'environment:group',
    //       // 'text',
    //     ]
    //   );
    // });

    // $this->tokens = array_filter($this->tokens, function ($token) {
    //   return $token->line_number >= 352 and $token->line_number <= 354;
    // });
  }

  protected function update($token)
  {
    if (str_contains($token->command_name, 'newtheorem')) {
      \TexStacks\Commands\TheoremEnv::add($token->command_content);
    }
  }

  private function addUpdatableCommand($command)
  {
    if (is_array($command)) {
      $this->updatable_commands = array_merge($this->updatable_commands, $command);
    } else {
      $this->updatable_commands[] = $command;
    }
  }
}
