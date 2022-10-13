<?php

namespace TexStacks\Parsers;

use TexStacks\Parsers\Node;
use TexStacks\Helpers\StrHelper;
use TexStacks\Parsers\SyntaxTree;
use TexStacks\Parsers\CommandNode;
use TexStacks\Parsers\SectionNode;
use TexStacks\Parsers\EnvironmentNode;
use TexStacks\Parsers\Token;

class LatexParser
{

  const DISPLAY_MATH_ENVIRONMENTS = [
    'equation',
    'equation*',
    'align',
    'align*',
    'multline',
    'multline*',
    'gather',
    'gather*',
    'flalign',
    'flalign*',
  ];

  const AMS_MATH_ENVIRONMENTS = [
    'math',
    'align', 'align*',
    'aligned',
    'alignedat', 'alignedat*',
    'alignat', 'alignat*',
    'flalign', 'flalign*',
    'subarray',
    'cases',
    'CD',
    'gather', 'gather*',
    'gathered', 'gathered*',
    'equation', 'equation*',
    'eqnarray', 'eqnarray*',
    'multline', 'multline*',
    'split',
    'matrix',
    'pmatrix',
    'smallmatrix',
    'Bmatrix', 'bmatrix',
    'Vmatrix', 'vmatrix',
  ];

  const SECTION_COMMANDS = [
    'part',
    'chapter', 'chapter\*',
    'section', 'section\*',
    'subsection', 'subsection\*',
    'subsubsection', 'subsubsection\*',
    'paragraph', 'paragraph\*',
    'subparagraph', 'subparagraph\*',
  ];

  const LIST_ENVIRONMENTS = [
    'itemize',
    'enumerate',    
    'compactenum',
    'compactitem',
    'asparaenum',
  ];

  const FONT_COMMANDS = [
    'textrm',
    'textsf',
    'texttt',
    'textmd',
    'textbf',
    'textup',
    'textit',
    'textsl',
    'textsc',
    'emph',
    'textnormal',
    'textsuperscript',
    'textsubscript',
  ];

  const TABULAR_ENVIRONMENTS = [
    'tabular',
    'supertabular',
    'array',
  ];

  protected SyntaxTree $tree;  
  private $current_node;
  private $parsed_line;
  private $tokens = [];
  private $buffer = '';
  private $line_number = 0;
  private $latex_src;
  private $pos;
  private $num_chars = 0;

  public function __construct()
  {
    $this->tree = new SyntaxTree();
    $root = new SectionNode([
      'id' => 0,
      'type' => 'section-cmd',
      'command_name' => 'document'
    ]);

    $this->tree->setRoot($root);

    $this->current_node = $root;
  }

  public function getRoot()
  {
    return $this->tree->root();
  }

  public function parse($latex_src_raw)
  {

    $lines = $this->getLines($latex_src_raw);
    // dd($lines);
    /* Parse line and add node to syntax tree using depth-first traversal */
    foreach ($lines as $number => $line) {

      $this->parsed_line = $this->parseLine($line, $number);

      $parse_result = match($this->parsed_line['type']) {

        'section-cmd' => $this->handleSectionNode(),

        'environment',
        'font-environment',
        'math-environment',
        'tabular-environment',
        'list-environment' => $this->handleEnvironmentNode(),

        'item' => $this->handleListItemNode(),

        'label' => $this->handleLabelNode(),

        'includegraphics' => $this->handleIncludeGraphicsNode(),
 
        default => $this->addToCurrentNode(),

      };
      
    }
    
  }

  private function parseLine($line, $number) {

    $commands = [
      'begin',
      'end',
      ...self::SECTION_COMMANDS,
      'label',
      'item',      
      'includegraphics',
    ];

    foreach ($commands as $command) {

      if ($match = $this->matchCommand($command, $line)) {
        return [...$match, 'line_number' => $number];
      }

    }

    return [
      'type' => 'text',
      'content' => $line,
      'line_number' => $number,
    ];

  }

  private function matchCommand($name, $line) {
    
    if (!preg_match('/^\\\\' . $name . '\s*/m', $line)) return false;

    $content = preg_match('/\{(?<content>[^}]*)\}/', $line, $match) ? $match['content'] : null;

    $options = preg_match('/\[(?<options>[^\]]*)\]/', $line, $match) ? $match['options'] : null;

    $type = $this->getCommandType($name, $content);

    return [
      'type' => $type,
      'command_name' => $name,
      'command_content' => $content,
      'command_options' => $options,        
      'command_src' => $line
    ];

  }

  private function getCommandType($name, $content) {

    if ($name == 'begin' || $name == 'end') {

      if (in_array($content, self::AMS_MATH_ENVIRONMENTS)) return 'math-environment';

      if (in_array($content, self::LIST_ENVIRONMENTS))     return 'list-environment';

      if (in_array($content, self::TABULAR_ENVIRONMENTS))  return 'tabular-environment';

      if (in_array($content, self::FONT_COMMANDS))         return 'font-environment';

      return 'environment';

    }

    if (in_array($name, self::SECTION_COMMANDS)) return 'section-cmd';

    return match ($name) {

      'includegraphics' => 'includegraphics',

      'label' => 'label',

      'caption' => 'caption',

      'item' => 'item',

      default => 'text',

    };

  }

  private function addToCurrentNode()
  {
    $this->tree->addNode(new Node(
      [
        'id' => $this->tree->nodeCount(),
        'type' => 'text',
        'body' => $this->parsed_line['content']
      ]
    ), parent: $this->current_node); 
  }

  private function handleSectionNode()
  {
    $new_node = $this->createCommandNode();
    $parent = $this->current_node;

    /* Move up the tree until we find the first sectioning command
       with a lower numbered depth level */
    while ($parent->depthLevel() >= $new_node->depthLevel()) {
      $parent = $parent->parent();
    }
    
    $this->tree->addNode($new_node, $parent);
    $this->current_node = $new_node;
    return true;
  }

  private function handleEnvironmentNode()
  {
    if ($this->parsed_line['command_name'] === 'begin') {
      $new_node = $this->createCommandNode();
      $this->tree->addNode($new_node, $this->current_node);
      $this->current_node = $new_node;
      return true;
    }
    
    if ($this->parsed_line['type'] !== 'list-environment') {
      $this->current_node = $this->current_node->parent();
      return true;
    }

    /* If parsed_line was the end of a list-environment 
    then we need to move up the tree to find the first
    list-environment node
    */
    $parent = $this->current_node;

    while ($parent && $parent->type() !== 'list-environment') {
      $parent = $parent->parent();
    }
    
    $this->current_node = $parent->parent();

    return true;
    
  }

  private function handleListItemNode()
  {
    $new_node = $this->createCommandNode();

    $parent = $this->current_node;
    
    /* Move up the tree until we find the parent list-environment */
    while ($parent->type() !== 'list-environment') {
      $parent = $parent->parent();
    }

    $this->tree->addNode($new_node, $parent);

    $this->current_node = $new_node;

    return true;
  }

  private function handleLabelNode()
  {
    $this->current_node->setLabel($this->parsed_line['command_content']);

    if ($this->current_node->type() === 'math-environment') {
      $new_node = $this->createCommandNode();
      $this->tree->addNode($new_node, $this->current_node);
    }
    return true;
  }

  private function handleIncludeGraphicsNode()
  {
    $new_node = $this->createCommandNode();
    $this->tree->addNode($new_node, $this->current_node);
    return true;
  }

  private function createCommandNode()
  {

    $args = ['id' => $this->tree->nodeCount(), ...$this->parsed_line];

    if ($this->parsed_line['type'] === 'section-cmd') {
      return new SectionNode($args);
    } 
    else if (preg_match('/environment/', $this->parsed_line['type'])) {
      return new EnvironmentNode($args);
    } 
    else 
    {
      return new CommandNode($args);
    }
  }

  private function getLines($latex_src_raw)
  {
    return explode("\n", $this->normalizeLatexSource($latex_src_raw));
  }

  private function normalizeLatexSource(string $latex_src): string
  {

    $html_src = preg_replace('/.*\\\begin\s*{document}[\s\n]*(.*)\\\end\s*{document}.*/sm', "$1", $latex_src);

    $html_src = StrHelper::DeleteLatexComments($html_src);

    $html_src = str_replace('``', '"', $html_src);

    // Replace less than and greater than symbols with latex commands
    // note space after/before \lt and \gt
    $html_src = str_replace('<', '&lt;', $html_src);
    $html_src = str_replace('>', '&gt;', $html_src);

    // Replace dollar sign with html entity
    $html_src = str_replace('\\$', '&#36;', $html_src);

    // foreach (self::FONT_COMMANDS as $command) {
    //   $html_src = preg_replace($this->cmdContentRegex($command), $this->beginEndWrapper($command) , $html_src);
    // }
    
    // Replace $$...$$ with \begin{equation*}...\end{equation*}
    // note space after \begin{equation*}
    $html_src = preg_replace('/\$\$(.*?)\$\$/s', '\\begin{equation*} $1\\end{equation*}', $html_src);

    // Then replace $...$ with \begin{math}...\end{math}
    // note space after \begin{math}
    // $html_src = preg_replace('/\$(.*?)\$/s', "\\begin{math} $1\\end{math}", $html_src);
    $html_src = preg_replace('/\$(.*?)\$/s', "\\($1\\)", $html_src);

    // Then replace \(...\) with \begin{math}...\end{math}
    // note space after \begin{math}
    // $html_src = str_replace('\(', "\\begin{math} ", $html_src);
    // $html_src = str_replace('\)', "\\end{math}", $html_src);

    // Replace \[...\] with \begin{equation*}...\end{equation*}
    // note space after \begin{equation*}
    $html_src = preg_replace('/([^\\\])(?:\\\)(?:\[)/', '$1\\begin{equation*} ', $html_src);
    $html_src = preg_replace('/^\s*(?:\\\)(?:\[)/m', '$1\\begin{equation*} ', $html_src);
    $html_src = str_replace('\]', '\end{equation*}', $html_src);

    // Put labels on new line and make caption command an environment
    $html_src = preg_replace('/' . $this->cmdRegex('label') . '/m', "\n$1\n", $html_src);
    $html_src = preg_replace('/\\\caption\s*\{(?<content>.*)\}/', '\\begin{caption} $1\\end{caption}', $html_src);

    // Replace more than two newlines with two newlines
    // $html_src = preg_replace('/\n{3,}/', "\n\n", $html_src);

    $this->latex_src = $html_src;
    
    return $this->tokenize();
    
    // return $this->putCommandsOnNewLine($html_src);
  }

  private function tokenize() {
 
    $this->num_chars = strlen($this->latex_src);
 
    $this->pos = 0;

    $this->line_number = $this->num_chars > 0 ? 1 : 0;
    
    while ($this->pos < $this->num_chars)
    {

      $char = $this->latex_src[$this->pos];

      if ($char === '\\')
      {

        $this->pos++;

        $command_name = '';

        // Check if control word (starts with alpha character)
        if ($this->pos < $this->num_chars && ctype_alpha($this->latex_src[$this->pos])) {

          // Get command name
          while ($this->pos < $this->num_chars && ctype_alpha($char = $this->latex_src[$this->pos])) {
            $command_name .= $char;
            $this->pos++;
          }
          
          // Decide how to tokenize command
          if (in_array($command_name, self::SECTION_COMMANDS))
          {

            try {
              $token = $this->tokenizeSection($command_name);
            } catch (\Exception $e) {
              die($e->getMessage());
            }
            
            $this->addToken($token);

          }
          else if ($command_name === 'begin' || $command_name === 'end')
          {
            
            try {
              $env = $this->getEnvName();
            } catch (\Exception $e) {
              die($e->getMessage());
            }
                                    
            if (in_array($env, self::DISPLAY_MATH_ENVIRONMENTS)) {

              $this->addToken(new Token([
                'type' => 'math-environment',
                'command_name' => $command_name,
                'command_content' => $env,
                'command_options' => '',
                'command_src' => "\\" . $command_name . "{" . $env. "}",
                'line_number' => $this->line_number,
              ]));

            }
            else
            {
              $this->buffer .= "\\" . $command_name . "{" . $env. "}";
            }

          }
          else if (in_array($command_name, self::FONT_COMMANDS))
          {
            //
          }
          else
          {
            $this->buffer .= "\\" . $command_name;
          }

        }
        else
        {
          // Control symbol
          $this->buffer .= $char . $this->latex_src[$this->pos];
          $this->pos++;

        }
        
      } 
      else
      {

        if ($char === "\n") $this->line_number++;
        
        $this->buffer .= $char;
        $this->pos++;

      }

    }

    $this->addBufferAsToken();

    dd($this->tokens);

  }

  private function addToken(Token $token) {

    $this->addBufferAsToken();

    $this->tokens[] = $token;

  }

  private function addBufferAsToken() {

    if ($this->buffer === '') return;

    $this->tokens[] = new Token([
      'type' => 'text',        
      'body' => $this->buffer,
      'line_number' => $this->line_number,
    ]);

    $this->buffer = '';
 
  }

  private function tokenizeSection($command_name): Token
  {

    $content = '';
    $options = '';
    $src = '\\' . $command_name;
    $STARRED = false;
    $TOC_ENTRY = false;

    $ALLOWED_CHARS = [' ', '*', '{', '['];

    while ($this->pos < $this->num_chars) {

      $char = $this->latex_src[$this->pos];

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid sectioning command");
      }

      if ($char === ' ') {
        $this->pos++;
        continue;
      }

      if ($char === '*') {

        if ($STARRED || $TOC_ENTRY) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        $this->pos++;
        $command_name .= '*';
        $src .= '*';
        $STARRED = true;
        continue;
      }

      if ($char === '[') {

        if ($TOC_ENTRY) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        $this->pos++;
        $options = '';

        $src .= '[';

        while ($this->pos < $this->num_chars && $this->latex_src[$this->pos] !== ']') {

          if ($this->latex_src[$this->pos] === "\n") {
            throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
          }

          $options .= $this->latex_src[$this->pos];
          $src .= $this->latex_src[$this->pos];
          $this->pos++;

        }

        if ($this->pos === $this->num_chars) {
          throw new \Exception("$src <--- Missing closing bracket ] for \\$command_name on line {$this->line_number}");
        }

        $src .= ']';

        $this->pos++;
        $TOC_ENTRY = true;
        continue;
      }

      if ($char === '{' ) {
          
          $this->pos++;
          $content = '';
          $src .= '{';

          $brace_count = 1;
  
          while ($this->pos < $this->num_chars && $brace_count > 0) {

            if ($this->latex_src[$this->pos] === "\n") {
              throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
            }

            if ($this->latex_src[$this->pos] !== '}') {

              $content .= $this->latex_src[$this->pos];
              $src .= $this->latex_src[$this->pos];

              if ($this->latex_src[$this->pos] === '{' && $this->latex_src[$this->pos-1] !== '\\') $brace_count++;

              $this->pos++;

            } else {

              if ($this->latex_src[$this->pos-1] !== '\\') $brace_count--;
 
              if ($brace_count > 0) {
                $content .= '}';
                $src .= '}';
                $this->pos++;
              }

            }

          }
  
          if ($this->pos === $this->num_chars) {
            throw new \Exception("$src <--- Missing closing brace } for \\$command_name on line {$this->line_number}");
          }

          $src .= '}';
            
          $this->pos++;
  
          return new Token([
            'type' => 'section-cmd',
            'command_name' => $command_name,
            'command_content' => $content,
            'command_options' => $options,
            'command_src' => $src,
            'line_number' => $this->line_number]);
  
      }
      
    }

    return new Token([
      'type' => 'section-cmd',
      'command_name' => $command_name,
      'command_content' => $content,
      'command_options' => $options,
      'command_src' => $src,
      'line_number' => $this->line_number]);
    
  }

  // private getCommandContent($text, $src, )
  // {
  //   $j++;
  //   $content = '';
  //   $src .= '{';

  //   $brace_count = 1;

  //   while ($j < $length && $brace_count > 0) {

  //     if ($latex_src[$j] === "\n") {
  //       throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
  //     }

  //     if ($latex_src[$j] !== '}') {

  //       $content .= $latex_src[$j];
  //       $src .= $latex_src[$j];

  //       if ($latex_src[$j] === '{' && $latex_src[$j-1] !== '\\') $brace_count++;

  //       $j++;

  //     } else {

  //       if ($latex_src[$j-1] !== '\\') $brace_count--;

  //       if ($brace_count > 0) {
  //         $content .= '}';
  //         $src .= '}';
  //         $j++;
  //       }

  //     }

  //   }

  //   if ($j === $length) {            
  //     throw new \Exception("$src <--- Missing closing brace } for \\$command_name on line {$this->line_number}");
  //   }

  //   $src .= '}';
      
  //   $j++;
  // }

  private function tokenizeNoOptionsCmd($latex_src, $i, $command_name, $type) : array 
  {

    $length = strlen($latex_src);

    $content = '';    
    $src = '\\' . $command_name;
        
    $ALLOWED_CHARS = [' ', '{'];

    $j = 0;

    while ($j < $length) {

      $char = $latex_src[$j];

      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
      }

      if ($char === ' ') {
        $j++;
        continue;
      }

      if ($char === '{' ) {
          
          $j++;
          $content = '';
          $src .= '{';

          $brace_count = 1;
  
          while ($j < $length && $brace_count > 0) {

            if ($latex_src[$j] === "\n") {
              throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
            }

            if ($latex_src[$j] !== '}') {

              $content .= $latex_src[$j];
              $src .= $latex_src[$j];

              if ($latex_src[$j] === '{' && $latex_src[$j-1] !== '\\') $brace_count++;

              $j++;

            } else {

              if ($latex_src[$j-1] !== '\\') $brace_count--;
 
              if ($brace_count > 0) {
                $content .= '}';
                $src .= '}';
                $j++;
              }

            }

          }
  
          if ($j === $length) {            
            throw new \Exception("$src <--- Missing closing brace } for \\$command_name on line {$this->line_number}");
          }

          $src .= '}';
            
          $j++;
  
          return [$i + $j, new Token([
            'type' => $type,
            'command_name' => $command_name,
            'command_content' => $content,
            'command_src' => $src,
            'line_number' => $this->line_number])
          ];
  
      }
      
    }

    return [$i + $j, new Token([
      'type' => 'section-cmd',
      'command_name' => $command_name,
      'command_content' => $content,
      'command_options' => $options,
      'command_src' => $src,
      'line_number' => $this->line_number])
    ];

  }
  
  private function getEnvName() {
        
    try {
      $this->eatSpaceUntilTarget('{');
    } catch (\Exception $e) {
      die($e->getMessage() . " in environment declaration on line {$this->line_number}");
    }
    
    $env = '';

    while ($this->pos < $this->num_chars && $this->latex_src[$this->pos] !== '}') {

      $char = $this->latex_src[$this->pos];

      if (!(ctype_alpha($char) || $char === '*')) {
        throw new \Exception($env . $char . " <--- Invalid environment name at line {$this->line_number}");
      }

      $env .= $char;
      $this->pos++;

    }

    if ($this->pos === $this->num_chars) {
      throw new \Exception("Expected } at line {$this->line_number}");
    }

    $this->pos++;

    return $env;
  
  }

  private function eatSpaceUntilTarget($target) {
              
      if ($this->pos === $this->num_chars) {
        throw new \Exception("Unexpected end of file on line {$this->line_number}");
      }

      $char = $this->latex_src[$this->pos];
      $this->pos++;
  
      while ($this->pos < $this->num_chars && $char === ' ') {
        $char = $this->latex_src[$this->pos];
        $this->pos++;
      }

      if ($char === $target) {
        return;
      }
      
      throw new \Exception("Parse error: missing $target on line {$this->line_number}");
  
  }

  private function putCommandsOnNewLine(string $latex_src): string
  {
    
    foreach (self::SECTION_COMMANDS as $command) {
      $latex_src = preg_replace($this->cmdWithOptionsRegex($command), "\n$1$2\n", $latex_src);
    }

    $latex_src = preg_replace($this->envBeginTabularRegex(), "\n$1$2\n", $latex_src);

    $latex_src = preg_replace($this->envBeginWithOptionsRegex(), "\n$1$2\n", $latex_src);

    $latex_src = preg_replace($this->envBeginNoOptionsRegex(), "\n$1$2\n", $latex_src);

    $latex_src = preg_replace($this->envBeginRegex('math'), "\n$1\n", $latex_src);

    foreach (self::FONT_COMMANDS as $command) {
      $latex_src = preg_replace($this->envBeginRegex($command), "\n$1\n", $latex_src);
    }

    $latex_src = preg_replace('/' . $this->cmdRegex('end') . '/m', "\n$1\n", $latex_src);

    $latex_src = preg_replace('/' . $this->itemRegex() . '/m', "\n$1\n", $latex_src);

    $latex_src = preg_replace($this->cmdWithOptionsRegex('includegraphics'), "\n$1$2\n", $latex_src);

    return trim($latex_src);
  }

  private function cmdWithOptionsRegex($command) {
    $sp = '[\s\n]*';
    $basic = $this->cmdRegex($command);
    $with_options = $sp . '(\\\\' . $command . '\s*\[[^\]]*\]\s*\{[^}]*\})' . $sp;
    $pattern = '/' . $with_options . '|' . $basic . '/m';
    return $pattern;
  }

  private function envBeginWithOptionsRegex()
  {

    $sp = '[\s\n]*';
    $basic = $sp . '(\\\\begin\s*\{[a-z]+\}\s*\[[^\]]*\])(?!\{.*\})' . $sp;
    $with_options = $sp . '(\\\\begin\s*\{[a-z]+\}\s*\[[^\]]*\])(?!\{.*\})' . $sp;
    $pattern = '/' . $with_options . '|' . $basic . '/m';
    return $pattern;
  }

  private function envBeginNoOptionsRegex()
  {
    $sp = '[\s\n]*';
    $pattern = $sp . '(\\\\begin\s*\{[a-z|*]+\})\s*(?!(\{.*\}|\[.*\]))' . $sp;
    return '/' . $pattern . '/m';
  }

  private function envBeginTabularRegex() {
    $sp = '[\s\n]*';
    $basic = $sp . '(\\\\begin\s*\{(?:tabular|array)\}\s*\{(.*)\})' . $sp;
    $with_options = $sp . '(\\\\begin\s*\{(?:tabular|array)\}\s*\[[a-z]*\]\s*\{(?:.*)\})' . $sp;
    $pattern = '/' . $with_options . '|' . $basic . '/m';
    return $pattern;
  }

  private function envBeginRegex($env) {
    $sp = '[\s\n]*';
    $pattern = '/' . $sp . '(\\\\begin{' . $env . '})' . $sp . '/m';
    return $pattern;
  }

  private function cmdRegex($command) {
    $sp = '[\s\n]*';
    $pattern = $sp . '(\\\\' . $command . '\s*\{[^}]*\})' . $sp;    
    return $pattern;
  }

  private function cmdContentRegex($command) {
    $sp = '[\s\n]*';
    $pattern = '/' . $sp . '\\\\' . $command . '\s*\{(.*?)\}' . $sp . '/m';
    return $pattern;
  }

  private function itemRegex() {
    $sp = '[\s\n]*';
    $command = 'item';
    $pattern = $sp . '(\\\\' . $command . '[^\s\n]*)' . $sp;
    return $pattern;
  }

  private function beginEndWrapper($command) {
    return '\\begin{'. $command .'}$1\\end{' . $command . '}';
  }

}
