<?php

namespace TexStacks\Parsers;

use TexStacks\Helpers\StrHelper;
use TexStacks\Parsers\Node;
use TexStacks\Parsers\SyntaxTree;
use TexStacks\Parsers\CommandNode;
use TexStacks\Parsers\SectionNode;
use TexStacks\Parsers\EnvironmentNode;
use TexStacks\Parsers\LatexLexer;
use TexStacks\Parsers\PreambleParser;

class LatexParser
{

  protected SyntaxTree $tree;
  private $current_node;
  private $lexer;
  private $section_counters;
  private $thm_envs = [];
  private $raw_src;
  private $src;
  private $preamble_parser;
  public readonly array $front_matter;

  public function __construct($data=[])
  {
    $this->preamble_parser = new PreambleParser;

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
      'paragraph' => 0,
      'subparagraph' => 0,
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

  public function setCitations($citations) : void
  {
    $this->lexer->setCitations($citations);
  }

  public function parse($latex_src_raw) : void
  {

    /* pre-process raw latex and setup values needed by the lexer before tokenizing */
    $this->init($latex_src_raw);

    try {
      $tokens = $this->lexer->tokenize($this->src);
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
        'group-environment',
        'displaymath-environment',
        'inlinemath',
        'verbatim-environment',
        'tabular-environment',
        'list-environment',
        'bibliography-environment' => 'handleEnvironmentNode',

        'thm-environment' => 'handleTheoremEnvironment',

        'item' => 'handleListItemNode',

        'bibitem' => 'handleBibItemNode',

        'label' => 'handleLabelNode',

        'symbol',
        'includegraphics',
        'caption',
        'cite',
        'ref',
        'eqref' => 'handleCommandNode',

        'font-declaration' => 'handleFontDeclaration',

        'font-cmd' => 'doNothing',
 
        default => 'addToCurrentNode',

      };

      try {
        $this->$handler($token);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage() . ' Trace: ' . $e->getTraceAsString());
      }
      
    }
    
    // dd($this->tree->root()->children());
    // dd($this->section_counters);
    // dd($this->getNewCommands());
    
  }

  private function init($latex_src_raw) : void
  {
    $this->raw_src = $latex_src_raw;

    $this->src = $this->preprocessRawSource();

    $this->preamble_parser->setSrc($this->src);

    $thm_envs = $this->preamble_parser->getTheoremEnvs();

    $this->thm_envs = array_merge($this->thm_envs, $thm_envs);
    
    $this->front_matter = $this->preamble_parser->getFrontMatter();
    
    $this->resetTheoremCounters();
 
    $this->lexer->setTheoremEnvs(array_keys($this->thm_envs));
  }

  private function preprocessRawSource()
  {

    $search_replace = 
    [
      '<'   =>   ' &lt; ',
      '>'   =>   ' &gt; ',
      '\\$' =>   '&#36;',
      '``'  =>   '"',
      '\/'  =>   ' ',
      "\'a" =>   '&aacute;',
      "\'e" =>   '&eacute;',
      "\'i" =>   '&iacute;',
      "\'o" =>   '&oacute;',
      "\'u" =>   '&uacute;',
      "\'y" =>   '&yacute;',
      "\'A" =>   '&Aacute;',
      "\'E" =>   '&Eacute;',
      "\'I" =>   '&Iacute;',
      "\'O" =>   '&Oacute;',
      "\'U" =>   '&Uacute;',
      "\'Y" =>   '&Yacute;',
      "\`a" =>   '&agrave;',
      "\`e" =>   '&egrave;',
      "\`i" =>   '&igrave;',
      "\`o" =>   '&ograve;',
      "\`u" =>   '&ugrave;',
      "\`y" =>   '&ygrave;',
      "\`A" =>   '&Agrave;',
      "\`E" =>   '&Egrave;',
      "\`I" =>   '&Igrave;',
      "\`O" =>   '&Ograve;',
      "\`U" =>   '&Ugrave;',
      "\`Y" =>   '&Ygrave;',
      "\^a" =>   '&acirc;',
      "\^i" =>   '&icirc;',
      "\^o" =>   '&ocirc;',
      "\^u" =>   '&ucirc;',
      "\^y" =>   '&ycirc;',
      "\^A" =>   '&Acirc;',
      "\^E" =>   '&Ecirc;',
      "\^I" =>   '&Icirc;',
      "\^O" =>   '&Ocirc;',
      "\^U" =>   '&Ucirc;',
      "\^Y" =>   '&Ycirc;',
      '\"a' =>   '&auml;',
      '\"e' =>   '&euml;',
      '\"i' =>   '&iuml;',
      '\"o' =>   '&ouml;',
      '\"u' =>   '&uuml;',
      '\"y' =>   '&yuml;',
      '\"A' =>   '&Auml;',
      '\"E' =>   '&Euml;',
      '\"I' =>   '&Iuml;',
      '\"O' =>   '&Ouml;',
      '\"U' =>   '&Uuml;',
      '\"Y' =>   '&Yuml;',
    ];

    return str_replace(array_keys($search_replace), array_values($search_replace), $this->raw_src);

  }

  public function generateMathJaxConfig() : string
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
      }
      else if ($command['type'] === 'with-args')
      {
        $mathjax_config['tex']['macros'][$command['cmd']] = [$command['defn'], $command['narg']];
      }
      else
      {
        $mathjax_config['tex']['macros'][$command['cmd']] = [$command['defn'], $command['narg'], $command['default']];
      }

    }
        
    return json_encode($mathjax_config);

  }

  public function getMathMacros() : string
  {
    return $this->preamble_parser->getMathMacros();
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

  private function doNothing()
  {
    return;
  }

  private function handleSectionNode($token) : void
  {
    $new_node = $this->createCommandNode($token);
    
    /* If new_node is part of verbatim environment 
     * then just add new_node to tree */

    if ($this->current_node->pathToRootHasType('verbatim-environment'))
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
    if ($token->type !== 'list-environment' && $token->type !== 'bibliography-environment') {
      $this->current_node = $this->current_node->parent();
      return;
    }

    /* If token was the end of a list-env/bibliography-env then we need to move up the tree 
      to find the first list-env/bibliography-env node */
 
    $parent = $this->current_node->parent()->closest($token->type);
    
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

    if (!$this->current_node->pathToRootHasType('verbatim-environment') && !$new_node->getArg('starred'))
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

  private function handleBibItemNode($token) : void
  {
    $new_node = $this->createCommandNode($token);

    $parent = $this->current_node->closest('bibliography-environment');

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

  private function handleFontDeclaration($token) : void
  {
    $this->current_node->addClass($token->body);
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
        'body' => "<div class=\"parse-error\">" . $message . "</div>",
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

    if ($increment) $this->resetSectionCounters($section_name);

    return implode('.', $section_numbers);

  }

  private function resetSectionCounters($section_name=null) : void
  {

    $flag = false;

    foreach ($this->section_counters as $key => $value)
    {
      if ($flag) $this->section_counters[$key] = 0;

      if ($key === $section_name) $flag = true;

    }

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

  private function getNewCommands() : array
  {

    $pattern = '/(\\\newcommand\s*\\\\[\sa-zA-Z\s]+|\\\renewcommand\s*\\\\[\sa-zA-Z\s]+|\\\newcommand|\\\renewcommand)/';

    preg_match_all($pattern, $this->src, $matches, PREG_OFFSET_CAPTURE);

    if (!isset($matches[1])) return [];
    
    $new_commands = [];
     
    foreach ($matches[1] as $match)
    {
      $offset = (int) $match[1];

      $args = [];
 
      if (substr_count($match[0], "\\") === 1)
      {
        $command = trim(str_replace("\\", '', $match[0]));

        $args = StrHelper::getAllCmdArgsOptions($command, substr($this->src, $offset));
                
      }
      else if (substr_count($match[0], "\\") === 2)
      {

        $to_remove = str_contains($match[0], "renewcommand") ? "\\renewcommand" : "\\newcommand";

        $command = trim(str_replace($to_remove, '', $match[0]));

        $cmd = str_replace("\\", '', trim($command));

        $offset += strlen($to_remove);
        
        $args = StrHelper::getAllCmdArgsOptions($cmd, substr($this->src, $offset));

        $cmd_array = [(object) ['type' => 'arg', 'value' => $cmd]];

        $args = [...$cmd_array, ...$args];
 
      }

      $signature = implode('-', array_map(fn($x) => $x->type, $args));

      if (count($args) === 2 && $signature === 'arg-arg')
        {
          $new_commands[] = [
            'type' => 'simple',
            'cmd' => str_replace("\\", '', trim($args[0]->value)),
            'defn' => trim($args[1]->value),
          ];
          continue;
        }

        if (count($args) === 3 && $signature === 'arg-option-arg')
        {
          $new_commands[] = [
            'type' => 'with-args',
            'cmd' => str_replace("\\", '', trim($args[0]->value)),
            'narg' => trim($args[1]->value),
            'defn' => trim($args[2]->value),
          ];
          continue;
        }

        if (count($args) === 4 && $signature === 'arg-option-option-arg')
        {
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
