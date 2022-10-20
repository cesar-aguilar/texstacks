<?php

namespace TexStacks\Parsers;

use TexStacks\Helpers\StrHelper;
use TexStacks\Parsers\Node;
use TexStacks\Parsers\SyntaxTree;
use TexStacks\Parsers\CommandNode;
use TexStacks\Parsers\SectionNode;
use TexStacks\Parsers\EnvironmentNode;
use TexStacks\Parsers\LatexLexer;

class LatexParser
{

  protected SyntaxTree $tree;
  private $current_node;
  private $lexer;
  private $section_counters;
  private $thm_envs = [];

  public function __construct($data=[])
  {
    $this->tree = new SyntaxTree();

    $this->thm_envs = $data['thm_env'] ?? [];

    $lexer_data = [
      'thm_env' => array_keys($this->thm_envs),
      'macros' => $data['macros'] ?? [],
    ];

    $this->lexer = new LatexLexer($lexer_data);

    $root = new SectionNode([
      'id' => 0,
      'type' => 'section-cmd',
      'command_name' => 'document'
    ]);

    $this->tree->setRoot($root);

    $this->current_node = $root;

    $this->section_counters = [
      'chapter' => 0,
      'section' => 0,
      'subsection' => 0,
      'subsubsection' => 0,
    ];

  }

  public function getRoot() : SectionNode
  {
    return $this->tree->root();
  }

  public function setRefLabels($labels) : void
  {
    $this->lexer->setRefLabels($labels);
  }

  public function parse($latex_src_raw) : void
  {

    $thm_envs = $this->getTheoremEnvs($latex_src_raw);

    $this->thm_envs = array_merge($this->thm_envs, $thm_envs);

    $this->resetTheoremCounters();
 
    $this->lexer->setTheoremEnvs(array_keys($this->thm_envs));

    try {
      $tokens = $this->lexer->tokenize($latex_src_raw);
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
    // dd($tokens);
    // die($this->lexer->prettyPrintTokens());
    /* From token add node to syntax tree using depth-first traversal */
    foreach ($tokens as $token)
    {

      $handler = match($token->type) {

        'section-cmd' => 'handleSectionNode',

        'environment',
        'displaymath-environment',
        'inlinemath',
        'verbatim',
        'tabular-environment',
        'list-environment' => 'handleEnvironmentNode',

        'thm-environment' => 'handleTheoremEnvironment',

        'item' => 'handleListItemNode',

        'label' => 'handleLabelNode',

        'symbol',
        'includegraphics',
        'caption',
        'ref',
        'eqref',
        'cite',
        'font-cmd' => 'handleCommandNode',
 
        default => 'addToCurrentNode',

      };

      try {
        $this->$handler($token);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }
      
    }
    
    // dd($this->tree->root()->children());
    // dd($this->section_counters);
    
  }

  private function addToCurrentNode($token) : void
  {
    $this->tree->addNode(new Node(
      [
        'id' => $this->tree->nodeCount(),
        'type' => 'text',
        'body' => $token->body,
        'line_number' => $token->line_number
      ]
    ), parent: $this->current_node); 
  }

  private function handleSectionNode($token) : void
  {
    $new_node = $this->createCommandNode($token);
    
    /* If new_node is part of verbatim environment 
     * then just add new_node to tree */

    if ($this->current_node->pathToRootHasType('verbatim'))
    {
      $this->tree->addNode($new_node, $this->current_node);
      return;
    }

    $section_name = $new_node->commandName();

    $new_node->setRefNum($this->getSectionNumber($section_name));

    $this->resetTheoremCounters($section_name);

    /* To set the parent for the new_node, move up the tree 
     * until we find the first sectioning command
     * with a lower numbered depth level */

    $parent = $new_node->closestParentSection($this->current_node);

    $this->tree->addNode($new_node, $parent);
    $this->current_node = $new_node;
    return;
  }

  private function handleEnvironmentNode($token) : void
  {
    if ($token->command_name === 'begin') {

      $new_node = $this->createCommandNode($token);
      $this->tree->addNode($new_node, $this->current_node);
      $this->current_node = $new_node;
      return;

    }

    /* End environment if not a list-environment and update current_node */
    if ($token->type !== 'list-environment') {      
      $this->current_node = $this->current_node->parent();
      return;
    }

    /* If token was the end of a list-env then we need to move up the tree 
      to find the first list-env node */
    $parent = $this->current_node->parent()->closest('list-environment');
    
    $this->current_node = $parent->parent() ?? $this->tree->root();

    return;
    
  }

  private function handleTheoremEnvironment($token) : void
  {

    if ($token->command_name === 'end')
    {
      $this->current_node = $this->current_node->parent();
      return;
    }
    
    $new_node = $this->createCommandNode($token);

    if (!$this->current_node->pathToRootHasType('verbatim') && !$new_node->getArg('starred'))
    {
      $env_name = $new_node->commandContent();

      $new_node->setRefNum($this->getTheoremNumber($env_name));
    }

    $this->tree->addNode($new_node, $this->current_node);
    $this->current_node = $new_node;
    return;

  }

  private function handleListItemNode($token) : void
  {
    $new_node = $this->createCommandNode($token);

    $parent = $this->current_node->closest('list-environment');
    
    $this->tree->addNode($new_node, $parent);

    $this->current_node = $new_node;

  }

  private function handleLabelNode($token) : void
  {
    $this->current_node->setLabel($token->command_content);

    if (!$this->current_node->hasType(['section-cmd', 'thm-environment'])) {      
      $this->current_node->setRefNum($token->command_options);
    }

    if ($this->current_node->hasType('displaymath-environment')) {
      $new_node = $this->createCommandNode($token);
      $this->tree->addNode($new_node, $this->current_node);
    }
    
  }

  private function handleCommandNode($token) : void
  {
    $new_node = $this->createCommandNode($token);
    $this->tree->addNode($new_node, $this->current_node);
  }

  private function createCommandNode($token) : mixed
  {

    $args = ['id' => $this->tree->nodeCount(), ...(array) $token];

    if ($token->type === 'section-cmd') {
      return new SectionNode($args);
    }
    else if ($token->type === 'thm-environment')
    {      
      return $this->createTheoremNode($token, $args);
    } 
    else if (preg_match('/environment/', $token->type))
    {
      return new EnvironmentNode($args);
    }
    else if ($token->type === 'symbol')
    {
      return new Node($args);
    }
    else
    {
      return new CommandNode($args);
    }
  }

  private function createTheoremNode($token, $args) : EnvironmentNode
  {

    $env_name = $token->command_content;
    $env = $this->thm_envs[$env_name];

    $args['command_args'] = ['text' => $env->text, 'style' => $env->style, 'starred' => $env->starred];
    
    return new EnvironmentNode($args);
  }

  private function getTheoremNumber($env_name) : string
  {
    $env = &$this->thm_envs[$env_name];

    if ($shared_env_name = $env->shared)
    {
      $shared_env = &$this->thm_envs[$shared_env_name];

      $shared_env->counter += 1;

      $counter = $shared_env->counter;

      $parent_counter = $this->getSectionNumber($shared_env->parent, increment: false);

      $counter = $parent_counter . '.' . $counter;      

    }
    else if ($env->parent)
    {
      
      $env->counter += 1;

      $counter = $env->counter;

      $parent_counter = $this->getSectionNumber($env->parent, increment: false);

      $counter = $parent_counter . '.' . $counter;

    }
    else
    {
      $counter = ++$env->counter;
    }

    return $counter;

  }

  public function terminateWithError($message) : void
  {

    $node = new Node(
      [
        'id' => $this->tree->nodeCount(),
        'type' => 'text',
        'body' => "<span class=\"parse-error\">" . $message . "</span>",
      ]
      );

    $this->tree->addNode($node, parent: $this->current_node);
    $this->tree->prependNode($node);

  }

  private function getSectionNumber($section_name, $increment=true) : string
  {

    if (str_contains($section_name, '*')) return '';

    $this->section_counters[$section_name] += $increment ? 1 : 0;

    $section_numbers = [];

    foreach ($this->section_counters as $key => $value) {

      if ($value) $section_numbers[] = $value;

      if ($key == $section_name) break;
    }

    return implode('.', $section_numbers);

  }

  /**
   * Reads preamble of $latex_src and returns
   * array of \newtheorem declarations as objects
   */
  private function getTheoremEnvs($latex_src) : array
  {

    preg_match_all('/(\\\newtheoremstyle|\\\newtheorem[*]?|\\\theoremstyle).*/', $latex_src, $matches, PREG_OFFSET_CAPTURE);

    if (!isset($matches[1])) return [];

    $thm_envs = [];
    
    $default_styles = ['plain', 'definition', 'remark'];
    $current_style = 'plain';

    foreach ($matches[1] as $match) {

      $command = str_replace("\\", '', $match[0]);
      $offset = $match[1];

      $args = StrHelper::getAllCmdArgsOptions($command, substr($latex_src, $offset));

      if ($command === 'theoremstyle' && isset($args[0])) {
        $current_style = in_array($args[0]->value, $default_styles) ? $args[0]->value : 'plain';
        continue;
      }

      if ($command === 'newtheorem' || $command === 'newtheorem*')
      {

        $num_args = count($args);

        if ($num_args !== 2 && $num_args !== 3) continue;

        $env = $args[0]->type === 'arg' ? $args[0]->value : '';

        // Declaration of the form \newtheorem(*?){env}{text}
        if ($num_args === 2) {

          $text = $args[1]->type === 'arg' ? $args[1]->value : '';
          $parent = null;
          $shared = null;

        }
        // Declaration of the form 
        // Parent: \newtheorem{env}{text}[parent-counter] or
        // Shared: \newtheorem{env}[shared]{text}
        else {
        
          // Case Parent
          if ($args[1]->type === 'arg') {
            $text = $args[1]->value;
            $parent = $args[2]->value;
            $shared = null;
          }
          else
          { // Case Shared
            $shared = $args[1]->value;
            $text = $args[2]->value;
            $parent = null;
          }

        }

        $starred = $command === 'newtheorem*';

        $thm_envs[$env] = (object) [          
          'text' => $text,
          'parent' => $parent,
          'shared' => $shared,
          'style' => $current_style,
          'starred' => $starred,
        ];

      }

    }

    return $thm_envs;

  }

  private function resetTheoremCounters($section_name=null) : void
  {

    if ($section_name === null)
    {
      foreach ($this->thm_envs as &$env) $env->counter = 0;
      return;
    }

    foreach ($this->thm_envs as &$env)
    {
      if ($env->parent === $section_name) $env->counter = 0;
    }

  }

  

  // private function parseLine($line, $number) {

  //   $commands = [
  //     'begin',
  //     'end',
  //     ...self::SECTION_COMMANDS,
  //     'label',
  //     'item',      
  //     'includegraphics',
  //   ];

  //   foreach ($commands as $command) {

  //     if ($match = $this->matchCommand($command, $line)) {
  //       return [...$match, 'line_number' => $number];
  //     }

  //   }

  //   return [
  //     'type' => 'text',
  //     'content' => $line,
  //     'line_number' => $number,
  //   ];

  // }

  // private function matchCommand($name, $line) {
    
  //   if (!preg_match('/^\\\\' . $name . '\s*/m', $line)) return false;

  //   $content = preg_match('/\{(?<content>[^}]*)\}/', $line, $match) ? $match['content'] : null;

  //   $options = preg_match('/\[(?<options>[^\]]*)\]/', $line, $match) ? $match['options'] : null;

  //   $type = $this->getCommandType($name, $content);

  //   return [
  //     'type' => $type,
  //     'command_name' => $name,
  //     'command_content' => $content,
  //     'command_options' => $options,        
  //     'command_src' => $line
  //   ];

  // }
    
  // private function putCommandsOnNewLine(string $latex_src): string
  // {
    
  //   foreach (self::SECTION_COMMANDS as $command) {
  //     $latex_src = preg_replace($this->cmdWithOptionsRegex($command), "\n$1$2\n", $latex_src);
  //   }

  //   $latex_src = preg_replace($this->envBeginTabularRegex(), "\n$1$2\n", $latex_src);

  //   $latex_src = preg_replace($this->envBeginWithOptionsRegex(), "\n$1$2\n", $latex_src);

  //   $latex_src = preg_replace($this->envBeginNoOptionsRegex(), "\n$1$2\n", $latex_src);

  //   $latex_src = preg_replace($this->envBeginRegex('math'), "\n$1\n", $latex_src);

  //   foreach (self::FONT_COMMANDS as $command) {
  //     $latex_src = preg_replace($this->envBeginRegex($command), "\n$1\n", $latex_src);
  //   }

  //   $latex_src = preg_replace('/' . $this->cmdRegex('end') . '/m', "\n$1\n", $latex_src);

  //   $latex_src = preg_replace('/' . $this->itemRegex() . '/m', "\n$1\n", $latex_src);

  //   $latex_src = preg_replace($this->cmdWithOptionsRegex('includegraphics'), "\n$1$2\n", $latex_src);

  //   return trim($latex_src);
  // }

  // private function cmdWithOptionsRegex($command) {
  //   $sp = '[\s\n]*';
  //   $basic = $this->cmdRegex($command);
  //   $with_options = $sp . '(\\\\' . $command . '\s*\[[^\]]*\]\s*\{[^}]*\})' . $sp;
  //   $pattern = '/' . $with_options . '|' . $basic . '/m';
  //   return $pattern;
  // }

  // private function envBeginWithOptionsRegex()
  // {

  //   $sp = '[\s\n]*';
  //   $basic = $sp . '(\\\\begin\s*\{[a-z]+\}\s*\[[^\]]*\])(?!\{.*\})' . $sp;
  //   $with_options = $sp . '(\\\\begin\s*\{[a-z]+\}\s*\[[^\]]*\])(?!\{.*\})' . $sp;
  //   $pattern = '/' . $with_options . '|' . $basic . '/m';
  //   return $pattern;
  // }

  // private function envBeginNoOptionsRegex()
  // {
  //   $sp = '[\s\n]*';
  //   $pattern = $sp . '(\\\\begin\s*\{[a-z|*]+\})\s*(?!(\{.*\}|\[.*\]))' . $sp;
  //   return '/' . $pattern . '/m';
  // }

  // private function envBeginTabularRegex() {
  //   $sp = '[\s\n]*';
  //   $basic = $sp . '(\\\\begin\s*\{(?:tabular|array)\}\s*\{(.*)\})' . $sp;
  //   $with_options = $sp . '(\\\\begin\s*\{(?:tabular|array)\}\s*\[[a-z]*\]\s*\{(?:.*)\})' . $sp;
  //   $pattern = '/' . $with_options . '|' . $basic . '/m';
  //   return $pattern;
  // }

  // private function envBeginRegex($env) {
  //   $sp = '[\s\n]*';
  //   $pattern = '/' . $sp . '(\\\\begin{' . $env . '})' . $sp . '/m';
  //   return $pattern;
  // }

  // private function cmdRegex($command) {
  //   $sp = '[\s\n]*';
  //   $pattern = $sp . '(\\\\' . $command . '\s*\{[^}]*\})' . $sp;    
  //   return $pattern;
  // }

  // private function cmdContentRegex($command) {
  //   $sp = '[\s\n]*';
  //   $pattern = '/' . $sp . '\\\\' . $command . '\s*\{(.*?)\}' . $sp . '/m';
  //   return $pattern;
  // }

  // private function itemRegex() {
  //   $sp = '[\s\n]*';
  //   $command = 'item';
  //   $pattern = $sp . '(\\\\' . $command . '[^\s\n]*)' . $sp;
  //   return $pattern;
  // }

  // private function beginEndWrapper($command) {
  //   return '\\begin{'. $command .'}$1\\end{' . $command . '}';
  // }

}
