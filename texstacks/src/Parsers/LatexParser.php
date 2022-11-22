<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Node;
use TexStacks\Helpers\StrHelper;
use TexStacks\Parsers\LatexLexer;
use TexStacks\Parsers\SyntaxTree;
use TexStacks\Parsers\CommandNode;
use TexStacks\Parsers\SectionNode;
use TexStacks\Helpers\SectionCounter;
use TexStacks\Parsers\PreambleParser;
use TexStacks\Parsers\EnvironmentNode;

class LatexParser
{

  private $called_internally = false;
  protected SyntaxTree $tree;
  private $current_node;
  private $sectionCounter;
  private $counters;
  private $thm_envs = [];
  private $raw_src;
  private $src;
  private $preamble_parser;

  public function __construct($args = [])
  {
    $this->called_internally = isset($args['called_internally']) ? $args['called_internally'] : false;

    $this->thm_envs = $args['thm_env'] ?? [];

    $this->sectionCounter = new SectionCounter($args['doc_class'] ?? 'article');

    $this->raw_src = $args['latex_src'] ?? '';

    $this->src = $this->preProcessRawSource();

    $this->preamble_parser = new PreambleParser($this->src);

    $this->init();
  }

  public function getRoot()
  {
    return $this->tree->root();
  }

  public function getSrc()
  {
    return $this->src;
  }

  public function parse($tokens): void
  {

    /* From token add node to syntax tree using depth-first traversal */
    foreach ($tokens as $token) {

      $handler = match ($token->type) {

        'cmd:section' => 'handleSectionNode',

        'environment',
        'environment:group',
        'inlinemath',
        'environment:displaymath',
        'environment:verbatim',
        'environment:tabular',
        'environment:list',
        'bibliography-environment' => 'handleEnvironmentNode',

        'environment:theorem' => 'handleTheoremEnvironment',

        'item' => 'handleListItemNode',

        'bibitem' => 'handleBibItemNode',

        'label' => 'handleLabelNode',

        'symbol',
        'alpha-symbol',
        'includegraphics',
        'cite',
        'cmd:font',
        'spacing-cmd',
        'accent-cmd',
        'ref',
        'eqref' => 'handleCommandNode',

        'cmd:font_declaration' => 'handleFontDeclaration',

        'caption' => 'handleCaptionNode',

        'two-args-cmd' => 'handleTwoArgCommandNode',

        'action-cmd' => 'handleActionCommandNode',

        'tag',
        'ignore' => 'doNothing',

        default => 'addToCurrentNode',
      };

      try {
        $this->$handler($token);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage() . ' Trace: ' . $e->getTraceAsString());
      }
    }

    // if (!$this->called_internally) {
    //   dd($this->tree->root()->children());
    // }
    // dd($this->section_counters);
    // dd($this->getNewCommands());

  }

  public function generateMathJaxConfig(): string
  {

    $new_commands = $this->getNewCommands();

    $mathjax_config = [
      'loader' => ['load' => ['ui/lazy']],
      'tex' => [
        'tags' => 'ams',
        'macros' => [],
      ]
    ];

    foreach ($new_commands as $command) {

      if ($command['type'] === 'simple') {
        $mathjax_config['tex']['macros'][$command['cmd']] = $command['defn'];
      } else if ($command['type'] === 'with-args') {
        $mathjax_config['tex']['macros'][$command['cmd']] = [$command['defn'], $command['narg']];
      } else {
        $mathjax_config['tex']['macros'][$command['cmd']] = [$command['defn'], $command['narg'], $command['default']];
      }
    }

    return json_encode($mathjax_config);
  }

  public function getMathMacros(): string
  {
    return $this->preamble_parser->getMathMacros();
  }

  public function getFrontMatter(): array
  {
    $front_matter = $this->preamble_parser->getFrontMatter();

    $front_matter['title'] = self::parseText($front_matter['title']);

    foreach ($front_matter['authors'] as $author) {
      $author->name = self::parseText($author->name);
    }

    return $front_matter;
  }

  public function getTheoremEnvs(): array
  {
    return array_keys($this->thm_envs);
  }

  public function terminateWithError($message): void
  {

    $node = new Node(
      [
        'id' => $this->tree->nodeCount(),
        'type' => 'text',
        'body' => "<div class=\"parse-error\">" . $message . "</div>",
      ]
    );

    $this->tree->addNode($node, parent: $this->current_node);
    $this->tree->prependNode($node);
  }

  private static function parseText($text, $line_number_offset = 0): Node
  {

    $parser = new self([
      'called_internally' => true,
      'latex_src' => $text,
    ]);

    $lexer = new LatexLexer(['line_number_offset' => $line_number_offset]);

    try {
      $tokens = $lexer->tokenize($parser->getSrc());
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    $parser->parse($tokens);

    return $parser->tree->root();
  }

  private function preProcessRawSource()
  {
    $search_replace = [
      '<' => '&lt;',
      '>' => '&gt;',
    ];

    return str_replace(array_keys($search_replace), array_values($search_replace), $this->raw_src);
  }

  private function init(): void
  {
    $thm_envs = $this->preamble_parser->getTheoremEnvs();

    $this->thm_envs = array_merge($this->thm_envs, $thm_envs);

    $this->resetTheoremCounters();

    $this->initTree();

    $this->initCounters();
  }

  private function initTree()
  {
    $this->tree = new SyntaxTree();

    $root = new Node([
      'id' => 0,
      'type' => 'root',
    ]);

    $this->tree->setRoot($root);

    $this->current_node = $root;
  }

  private function initCounters()
  {

    $this->counters = [
      'footnote' => ['value' => 0, 'parent' => null],
      'figure' => ['value' => 0, 'parent' => null],
      'table' => ['value' => 0, 'parent' => null],
    ];

    $number_within_cmds = $this->preamble_parser->getNumberWithin();

    foreach ($number_within_cmds as $args) {
      if ($args[0]->type === 'arg' && $args[1]->type === 'arg') {
        $counter_name = $args[0]->value;
        $parent_counter_name = $args[1]->value;
        $this->counters[$counter_name]['parent'] = $parent_counter_name;
      }
    }
  }

  private function addToCurrentNode($token): void
  {
    if ($this->current_node->isLeaf()) {
      $token->body = ltrim($token->body, "\n");
    }

    $this->tree->addNode(new Node(
      [
        'id' => $this->tree->nodeCount(),
        'type' => 'text',
        'body' => $token->body,
        'line_number' => $token->line_number
      ]
    ), parent: $this->current_node);
  }

  private function doNothing()
  {
    return;
  }

  private function handleSectionNode($token): void
  {
    $new_node = $this->createCommandNode($token);

    /* If new_node is part of verbatim environment 
     * then just add new_node to tree */

    if ($this->current_node->pathToRootHasType('environment:verbatim')) {
      $this->tree->addNode($new_node, $this->current_node);
      return;
    }

    $section_name = $new_node->commandName();

    $new_node->setRefNum($this->sectionCounter->get($section_name));

    $this->resetChildrenCounters($section_name);

    $this->resetTheoremCounters($section_name);

    if ($new_node->commandContent() && StrHelper::isNotAlpha($new_node->commandContent())) {
      $section_content = self::parseText($new_node->commandContent(), $new_node->line_number);
      $new_node->setCommandContent($section_content);
    }

    /* To set the parent for the new_node, move up the tree 
     * until we find the first sectioning command
     * with a lower numbered depth level */

    $parent = $new_node->closestParentSection($this->current_node) ?? $this->tree->root();

    $this->tree->addNode($new_node, $parent);
    $this->current_node = $new_node;
    return;
  }

  private function handleEnvironmentNode($token): void
  {
    if ($token->command_name === 'begin') {

      $new_node = $this->createCommandNode($token);

      if ($new_node->commandContent() === 'proof' && $new_node->commandOptions() != '') {
        $env_options = self::parseText($new_node->commandOptions(), $new_node->line_number);
        $new_node->setOptions($env_options);
      }

      $this->tree->addNode($new_node, $this->current_node);
      $this->current_node = $new_node;
      return;
    }

    /* End environment if not a list-environment and update current_node */
    if ($token->type !== 'environment:list' && $token->type !== 'bibliography-environment') {
      $this->current_node = $this->current_node->parent();
      return;
    }

    /* If token was the end of a list-env/bibliography-env then we need to move up the tree 
      to find the first list-env/bibliography-env node */

    $parent = $this->current_node->parent()->closest($token->type);

    $this->current_node = $parent->parent() ?? $this->tree->root();

    return;
  }

  private function handleTheoremEnvironment($token): void
  {

    if ($token->command_name === 'end') {
      $this->current_node = $this->current_node->parent();
      return;
    }

    $new_node = $this->createCommandNode($token);

    if (!$this->current_node->pathToRootHasType('environment:verbatim') && !$new_node->getArg('starred')) {
      $env_name = $new_node->commandContent();

      $new_node->setRefNum($this->getTheoremNumber($env_name));
    }

    if ($new_node->commandOptions() && StrHelper::isNotAlpha($new_node->commandOptions())) {
      $thm_options = self::parseText($new_node->commandOptions(), $new_node->line_number);
      $new_node->setOptions($thm_options);
    }

    $this->tree->addNode($new_node, $this->current_node);
    $this->current_node = $new_node;

    return;
  }

  private function handleListItemNode($token): void
  {
    $new_node = $this->createCommandNode($token);

    $parent = $this->current_node->closest('environment:list');

    $this->tree->addNode($new_node, $parent);

    $this->current_node = $new_node;
  }

  private function handleBibItemNode($token): void
  {
    $new_node = $this->createCommandNode($token);

    $parent = $this->current_node->closest('bibliography-environment');

    $this->tree->addNode($new_node, $parent);

    $this->current_node = $new_node;
  }

  private function handleLabelNode($token): void
  {
    $this->current_node->setLabel($token->command_content);

    if (!$this->current_node->hasType(['cmd:section', 'environment:theorem'])) {
      $this->current_node->setRefNum($token->command_options);
    }

    if ($this->current_node->hasType('environment:displaymath')) {
      $new_node = $this->createCommandNode($token);
      $this->tree->addNode($new_node, $this->current_node);

      // Add a \tag command using ref_num from token
      $this->tree->addNode(new CommandNode(
        [
          'id' => $this->tree->nodeCount(),
          'type' => 'tag',
          'command_src' => "\\tag{" . $token->command_options . "}",
          'body' => $token->command_options,
          'line_number' => $token->line_number
        ]
      ), parent: $this->current_node);
    } else if ($caption = $this->current_node->findChild('caption')) {
      $caption->setRefNum($this->current_node->commandRefNum());
    }
  }

  private function handleCommandNode($token): void
  {
    $new_node = $this->createCommandNode($token);

    if ($new_node->hasType('cmd:font') && StrHelper::isNotAlpha($new_node->commandContent())) {
      $command_content = self::parseText($new_node->commandContent(), $new_node->line_number);
      $new_node->setCommandContent($command_content);
    }

    if ($new_node->hasType('cmd:font') && $new_node->commandName() === 'footnote')
      $new_node->setRefNum($this->getCounter('footnote'));

    $this->tree->addNode($new_node, $this->current_node);
  }

  private function handleCaptionNode($token): void
  {
    $new_node = $this->createCommandNode($token);

    if (StrHelper::isNotAlpha($new_node->commandContent())) {
      $command_content = self::parseText($new_node->commandContent(), $new_node->line_number);
      $new_node->setCommandContent($command_content);
    }

    // Get and set figure counter on parent element
    if ($this->current_node->commandContent() === 'figure') {
      $counter = $this->getCounter('figure');
      $this->current_node->setRefNum($counter);
      $new_node->setRefNum($counter);
    } else if ($this->current_node->commandContent() === 'table') {
      $counter = $this->getCounter('table');
      $this->current_node->setRefNum($counter);
      $new_node->setRefNum($counter);
    }

    $this->tree->addNode($new_node, $this->current_node);
  }

  private function handleFontDeclaration($token): void
  {
    $this->current_node->addClass($token->body);
    $new_node = $this->createCommandNode($token);
    $this->tree->addNode($new_node, $this->current_node);
  }

  private function handleTwoArgCommandNode($token): void
  {
    if ($token->command_name === 'texorpdfstring') {
      $new_node = $this->createCommandNode($token);

      if ($new_node->getArg('arg1')) {
        $arg1 = self::parseText($new_node->getArg('arg1'), $new_node->line_number);
        $new_node->setCommandContent($arg1);
      }

      $this->tree->addNode($new_node, $this->current_node);

      return;
    }
  }

  private function handleActionCommandNode($token): void
  {
    // if ($token->command_name === 'appendix') $this->appendixMode();
  }

  private function createCommandNode($token): mixed
  {

    $args = ['id' => $this->tree->nodeCount(), ...(array) $token];

    if ($token->type === 'cmd:section') {
      return new SectionNode($args);
    } else if ($token->type === 'environment:theorem') {
      return $this->createTheoremNode($token, $args);
    } else if (preg_match('/environment/', $token->type)) {
      return new EnvironmentNode($args);
    } else if ($token->type === 'symbol') {
      return new Node($args);
    } else {
      return new CommandNode($args);
    }
  }

  private function createTheoremNode($token, $args): EnvironmentNode
  {

    $env_name = $token->command_content;
    $env = $this->thm_envs[$env_name];

    $args['command_args'] = ['text' => $env->text, 'style' => $env->style, 'starred' => $env->starred];

    return new EnvironmentNode($args);
  }

  private function getTheoremNumber($env_name): string
  {
    $env = &$this->thm_envs[$env_name];

    if ($shared_env_name = $env->shared) {
      $shared_env = &$this->thm_envs[$shared_env_name];

      $shared_env->counter += 1;

      $counter = $shared_env->counter;

      if ($shared_env->parent && $this->sectionCounter->isCounter($shared_env->parent)) {
        $parent_counter = $this->sectionCounter->get($shared_env->parent, increment: false);
        $counter = $parent_counter . '.' . $counter;
      }
    } else if ($env->parent) {

      $env->counter += 1;

      $counter = $env->counter;

      $parent_counter = $this->sectionCounter->get($env->parent, increment: false);

      $counter = $parent_counter . '.' . $counter;
    } else {
      $counter = ++$env->counter;
    }

    return $counter;
  }

  private function resetChildrenCounters($section_name = null): void
  {
    foreach ($this->counters as $key => $value) {
      if ($this->counters[$key]['parent'] === $section_name) $this->counters[$key]['value'] = 0;
    }
  }

  private function resetTheoremCounters($section_name = null): void
  {

    if ($section_name === null) {
      foreach ($this->thm_envs as &$env) $env->counter = 0;
      return;
    }

    foreach ($this->thm_envs as &$env) {
      if ($env->parent === $section_name) $env->counter = 0;
    }
  }

  private function getCounter($counter)
  {

    $parent_counter = $this->counters[$counter]['parent'];

    $value = $this->sectionCounter->get($parent_counter, increment: false);

    $num = ++$this->counters[$counter]['value'];

    return $value ? $value . '.' . $num : $num;
  }

  private function getNewCommands(): array
  {

    $pattern = '/(\\\newcommand\s*\\\\[\sa-zA-Z\s]+|\\\renewcommand\s*\\\\[\sa-zA-Z\s]+|\\\newcommand|\\\renewcommand)/';

    preg_match_all($pattern, $this->src, $matches, PREG_OFFSET_CAPTURE);

    if (!isset($matches[1])) return [];

    $new_commands = [];

    foreach ($matches[1] as $match) {
      $offset = (int) $match[1];

      $args = [];

      if (substr_count($match[0], "\\") === 1) {
        $command = trim(str_replace("\\", '', $match[0]));

        $args = StrHelper::getAllCmdArgsOptions($command, substr($this->src, $offset));
      } else if (substr_count($match[0], "\\") === 2) {

        $to_remove = str_contains($match[0], "renewcommand") ? "\\renewcommand" : "\\newcommand";

        $command = trim(str_replace($to_remove, '', $match[0]));

        $cmd = str_replace("\\", '', trim($command));

        $offset += strlen($to_remove);

        $args = StrHelper::getAllCmdArgsOptions($cmd, substr($this->src, $offset));

        $cmd_array = [(object) ['type' => 'arg', 'value' => $cmd]];

        $args = [...$cmd_array, ...$args];
      }

      $signature = implode('-', array_map(fn ($x) => $x->type, $args));

      if (count($args) === 2 && $signature === 'arg-arg') {
        $new_commands[] = [
          'type' => 'simple',
          'cmd' => str_replace("\\", '', trim($args[0]->value)),
          'defn' => trim($args[1]->value),
        ];
        continue;
      }

      if (count($args) === 3 && $signature === 'arg-option-arg') {
        $new_commands[] = [
          'type' => 'with-args',
          'cmd' => str_replace("\\", '', trim($args[0]->value)),
          'narg' => trim($args[1]->value),
          'defn' => trim($args[2]->value),
        ];
        continue;
      }

      if (count($args) === 4 && $signature === 'arg-option-option-arg') {
        $new_commands[] = [
          'type' => 'with-args-default',
          'cmd' => str_replace("\\", '', trim($args[0]->value)),
          'narg' => trim($args[1]->value),
          'default' => trim($args[2]->value),
          'defn' => trim($args[3]->value),
        ];
        continue;
      }
    }

    return $new_commands;
  }
}
