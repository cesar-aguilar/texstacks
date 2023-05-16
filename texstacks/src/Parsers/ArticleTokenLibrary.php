<?php

namespace TexStacks\Parsers;

class ArticleTokenLibrary
{

  private $command_groups = [];
  private $default_env;
  private $updatable_commands = [];

  /**
   * 
   */
  public function __construct($data = [])
  {
    $this->default_env = \TexStacks\Commands\Environment::class;

    $this->addUpdatableCommand(\TexStacks\Commands\Core\NewTheorem::list());
    $this->addUpdatableCommand(\TexStacks\Commands\Core\NewCommands::list());

    if (isset($data['thm_env'])) {
      \TexStacks\Commands\TheoremEnv::add($data['thm_env']);
    }

    if (isset($data['citations'])) {
      \TexStacks\Commands\Core\Citations::add($data['citations']);
    }

    if (isset($data['ref_labels'])) {
      \TexStacks\Commands\Core\ReferenceCommands::add($data['ref_labels']);
    }

    $this->command_groups = [
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
      \TexStacks\Commands\CustomMacros::class,
    ];
  }
  
  /**
   * 
   */
  public function getCommandGroups()
  {
    return $this->command_groups;
  }

  /**
   * 
   */
  public function defaultEnv()
  {
    return $this->default_env;
  }

  /**
   * 
   */
  public function isUpdatable($command_name) {
    return in_array($command_name, $this->updatable_commands);
  }

  /**
   * 
   */
  public function update($token)
  {
    if (str_contains($token->command_name, 'newtheorem')) {
      \TexStacks\Commands\TheoremEnv::add($token->command_content);
    }

    else if ($token->type === 'cmd:newcommand') {
      // Register the new command token in CustomMacros
      \TexStacks\Commands\CustomMacros::customAdd($token);
    }

  }

  /**
   * 
   */
  private function registerCommandGroup($class_name)
  {
    if (is_array($class_name)) {
      foreach ($class_name as $name) {
        $this->command_groups[] = $name;
      }
      return;
    }

    $this->command_groups[] = $class_name;
  }

  /**
   * 
   */
  private function addUpdatableCommand($command)
  {
    if (is_array($command)) {
      $this->updatable_commands = array_merge($this->updatable_commands, $command);
    } else {
      $this->updatable_commands[] = $command;
    }
  }



}
