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

  const ONE_ARGS_CMDS = [
    'label',
    'ref',
    'eqref',    
  ];

  const ONE_ARGS_CMDS_PRE_OPTIONS = [
    'includegraphics',
    'caption',
  ];

  const CMDS_POST_OPTIONS = [
    'item',
  ];

  const ENVS_POST_OPTIONS = [
    'itemize',
    'enumerate',
    'compactenum',
    'compactitem',
    'asparaenum',
    'figure',
    'table',
    'center',
    'verbatim',
  ];

  const AMS_THEOREM_ENVIRONMENTS = [
    'theorem',
    'proposition',
    'lemma',
    'corollary',
    'definition',
    'conjecture',
  ];
  
  protected SyntaxTree $tree;  
  private $current_node;
  private $parsed_line;
  private array $tokens = [];
  private string $buffer = '';
  private int $line_number = 0;
  private string $stream;
  private int $cursor;
  private string $prev_char;
  private int $num_chars = 0;
  private string $command_name;
  
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

    $this->normalizeLatexSource($latex_src_raw);
    
    $this->tokenize();
    // dd($this->tokens);
    /* From token add node to syntax tree using depth-first traversal */
    foreach ($this->tokens as $token)
    {

      $handler = match($token->type) {

        'section-cmd' => 'handleSectionNode',

        'environment',        
        'math-environment',
        'tabular-environment',        
        'list-environment' => 'handleEnvironmentNode',

        'item' => 'handleListItemNode',

        'label' => 'handleLabelNode',

        'includegraphics',
        'caption',
        'font-environment' => 'handleCommandNode',
 
        default => 'addToCurrentNode',

      };

      $this->$handler($token);
      
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

  private function getCommandType($name, $content=null)
  {

    if ($name == 'begin' || $name == 'end') {

      if (in_array($content, self::DISPLAY_MATH_ENVIRONMENTS)) return 'math-environment';

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

      'ref' => 'ref',

      'eqref' => 'eqref',

      default => 'text',

    };

  }

  private function addToCurrentNode($token)
  {
    $this->tree->addNode(new Node(
      [
        'id' => $this->tree->nodeCount(),
        'type' => 'text',
        'body' => $token->body,
      ]
    ), parent: $this->current_node); 
  }

  private function handleSectionNode($token)
  {
    $new_node = $this->createCommandNode($token);
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

  private function handleEnvironmentNode($token)
  {
    if ($token->command_name === 'begin') {
      $new_node = $this->createCommandNode($token);
      $this->tree->addNode($new_node, $this->current_node);
      $this->current_node = $new_node;
      return true;
    }

    if ($token->type !== 'list-environment') {      
      $this->current_node = $this->current_node->parent();
      return true;
    }

    /* If token was the end of a list-environment 
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

  private function handleListItemNode($token)
  {
    $new_node = $this->createCommandNode($token);

    $parent = $this->current_node;
    
    /* Move up the tree until we find the parent list-environment */
    while ($parent->type() !== 'list-environment') {
      $parent = $parent->parent();
    }

    $this->tree->addNode($new_node, $parent);

    $this->current_node = $new_node;

    return true;
  }

  private function handleLabelNode($token)
  {
    $this->current_node->setLabel($token->command_content);

    if ($this->current_node->type() === 'math-environment') {
      $new_node = $this->createCommandNode($token);
      $this->tree->addNode($new_node, $this->current_node);
    }
    return true;
  }

  private function handleCommandNode($token)
  {
    $new_node = $this->createCommandNode($token);
    $this->tree->addNode($new_node, $this->current_node);
    return true;
  }

  private function createCommandNode($token)
  {
    
    $args = ['id' => $this->tree->nodeCount(), ...(array) $token];

    if ($token->type === 'section-cmd') {
      return new SectionNode($args);
    } 
    else if (preg_match('/environment/', $token->type)) {
      return new EnvironmentNode($args);
    } 
    else 
    {
      return new CommandNode($args);
    }
  }

  private function normalizeLatexSource(string $latex_src)
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
    // $html_src = preg_replace('/' . $this->cmdRegex('label') . '/m', "\n$1\n", $html_src);
    // $html_src = preg_replace('/\\\caption\s*\{(?<content>.*)\}/', '\\begin{caption} $1\\end{caption}', $html_src);

    // Replace more than two newlines with two newlines
    $html_src = preg_replace('/\n{3,}/', "\n\n", $html_src);

    $this->stream = $html_src;

    // return $this->tokenize();
    
    // return $this->putCommandsOnNewLine($html_src);
  }

  private function getChar() {
    return $this->cursor < $this->num_chars ? $this->stream[$this->cursor] : null;
  }

  private function getNextChar() {
    $this->prev_char = $this->getChar();
    $this->cursor++;
    return $this->getChar();
  }

  private function tokenize() {
 
    $this->num_chars = strlen($this->stream);

    if ($this->num_chars === 0) return [];
 
    $this->cursor = -1;

    $this->line_number = 1;
    
    while (!is_null($char = $this->getNextChar()))
    {

      if ($char !== '\\') {
        if ($char === "\n") $this->line_number++;
        $this->buffer .= $char;
        continue;
      }

      $char = $this->getNextChar();

      // If char is non-alphabetic then we have a control symbol
      if (!ctype_alpha($char ?? '')) {
        $this->buffer .= "\\" . $char;
        continue;
      }
      
      $this->command_name = $this->consumeUntilNonAlpha();

      // Make token
      if (in_array($this->command_name, self::SECTION_COMMANDS))
      {
        try {
          $token = $this->tokenizeSection();
        } catch (\Exception $e) {
          die($e->getMessage());
        }
        
        $this->addToken($token);
        
      }
      else if ($this->command_name === 'begin' || $this->command_name === 'end')
      {

        try {
          $env = $this->getEnvName();
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        if ($this->command_name === 'end')
        {
          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_src' => "\\end{" . $env. "}",
            'line_number' => $this->line_number,
          ]));
        }
        else if (in_array($env, self::DISPLAY_MATH_ENVIRONMENTS))
        {

          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_options' => '',
            'command_src' => "\\" . $this->command_name . "{" . $env. "}",
            'line_number' => $this->line_number,
          ]));

        }
        else if (in_array($env, [...self::ENVS_POST_OPTIONS, ...self::AMS_THEOREM_ENVIRONMENTS]))
        {
          try {
            $options = $this->getContentBetweenDelimiters('[', ']');
          } catch (\Exception $e) {
            die($e->getMessage());
          }

          $command_src = "\\" . $this->command_name . "{" . $env . "}";

          if ($options !== '') $command_src .= "[" . $options . "]";

          $type = $this->getCommandType($this->command_name, $env);

          $this->addToken(new Token([
            'type' => $type,
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_options' => $options,
            'command_src' => $command_src,
            'line_number' => $this->line_number,
          ]));

        }
        else if (in_array($env, self::TABULAR_ENVIRONMENTS))
        {
          try {
            $options = $this->getContentBetweenDelimiters('[', ']');
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
          }

          $command_src = "\\" . $this->command_name . "{" . $env . "}";

          if ($options !== '') $command_src .= "[" . $options . "]";

          try {
            $args = $this->getContentBetweenDelimiters('{', '}');
          } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
          }

          if ($args === '') {
            throw new \Exception("Missing arguments for tabular environment on line " . $this->line_number);
          }

          $command_src .= "{" . $args . "}";

          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_args' => [$args],
            'command_options' => $options,
            'command_src' => $command_src,
            'line_number' => $this->line_number,
          ]));

        }
        else
        {
          $this->addToken(new Token([
            'type' => $this->getCommandType($this->command_name, $env),
            'command_name' => $this->command_name,
            'command_content' => $env,
            'command_src' => "\\begin{" . $env. "}",
            'line_number' => $this->line_number,
          ]));
        }

      }
      else if (in_array($this->command_name, self::FONT_COMMANDS))
      {

        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $this->addToken(new Token([
          'type' => 'font-environment',
          'command_name' => $this->command_name,
          'command_content' => $content,
          'command_options' => '',
          'command_src' => "\\" . $this->command_name . "{" . $content. "}",
          'body' => $content,
          'line_number' => $this->line_number,
        ]));

      }
      else if (in_array($this->command_name, self::ONE_ARGS_CMDS))
      {
        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $this->addToken(new Token([
          'type' => $this->getCommandType($this->command_name),
          'command_name' => $this->command_name,
          'command_content' => $content,
          'command_options' => '',
          'command_src' => "\\" . $this->command_name . "{" . $content. "}",
          'body' => $content,
          'line_number' => $this->line_number,
        ]));
      }
      else if (in_array($this->command_name, self::ONE_ARGS_CMDS_PRE_OPTIONS))
      {
        try {
          $token = $this->tokenizeCmdWithOptionsArg();
        } catch (\Exception $e) {
          die($e->getMessage());
        }
 
        $this->addToken($token);
      }
      else if (in_array($this->command_name, self::CMDS_POST_OPTIONS))
      {
        try {
          $token = $this->tokenizeCmdWithOptions();
        } catch (\Exception $e) {
          die($e->getMessage());
        }
        
        $this->addToken($token);
      }
      else
      {
        $this->buffer .= "\\" . $this->command_name . $this->getChar();
      }

    }

    $this->addBufferAsToken();
    
  }

  private function addToken(Token $token) {

    $this->addBufferAsToken();

    $this->tokens[] = $token;

  }

  private function addBufferAsToken()
  {

    if ($this->buffer === '') return;

    $this->tokens[] = new Token([
      'type' => 'text',        
      'body' => $this->buffer,
      'line_number' => $this->line_number,
    ]);

    $this->buffer = '';
 
  }

  private function tokenizeSection(): Token
  {

    $content = '';
    $options = '';
    $src = '\\' . $this->command_name;
    $STARRED = false;
    $TOC_ENTRY = false;

    $ALLOWED_CHARS = [' ', '*', '{', '['];

    while (!is_null($char = $this->getChar())) {
 
      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid sectioning command");
      }

      if ($char === ' ') {
        $this->cursor++;
        continue;
      }

      if ($char === '*') {

        if ($STARRED || $TOC_ENTRY) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        $this->cursor++;
        $this->command_name .= '*';
        $src .= '*';
        $STARRED = true;
        continue;
      }

      if ($char === '[') {

        if ($TOC_ENTRY) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        try {
          $options = $this->getContentUpToDelimiter(']');
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $src .= '[' . $options . ']';

        $this->cursor++;
        $TOC_ENTRY = true;
        continue;
      }

      if ($char === '{' ) {
 
        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $src .= '{' . $content . '}';
        break;          
      }

    }

    return new Token([
      'type' => 'section-cmd',
      'command_name' => $this->command_name,
      'command_content' => $content,
      'command_options' => $options,
      'command_src' => $src,
      'line_number' => $this->line_number,
    ]);
    
  }

  private function tokenizeCmdWithOptionsArg(string|null $type=null) : Token
  {
    $content = '';
    $options = '';
    $src = '\\' . $this->command_name;    
    $OPTIONS = false;

    $ALLOWED_CHARS = [' ', '{', '['];

    while (!is_null($char = $this->getChar())) {
 
      if (!in_array($char, $ALLOWED_CHARS)) {
        $src .= $char;
        throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
      }

      if ($char === ' ') {
        $this->cursor++;
        continue;
      }

      if ($char === '[') {

        if ($OPTIONS) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        try {
          $options = $this->getContentUpToDelimiter(']');
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $src .= '[' . $options . ']';

        $this->cursor++;
        $OPTIONS = true;
        continue;
      }

      if ($char === '{' ) {
 
        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $src .= '{' . $content . '}';
        
        break;
  
      }

    }

    return new Token([
      'type' => $this->getCommandType($this->command_name),
      'command_name' => $this->command_name,
      'command_content' => $content,
      'command_options' => $options,
      'command_src' => $src,
      'body' => $content,
      'line_number' => $this->line_number,
    ]);
  
  }

  private function tokenizeCmdWithArgOptions(string|null $type=null) : Token
  {
    $content = '';
    $options = '';
    $src = '\\' . $this->command_name;    
    $ARGS_DONE = false;

    $ALLOWED_CHARS = [' ', '{', '['];

    while (!is_null($char = $this->getChar())) {
 
      if (!in_array($char, $ALLOWED_CHARS)) {
        if (!$ARGS_DONE) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }
        $this->cursor--;
        break;
      }

      if ($char === ' ') {
        $this->cursor++;
        continue;
      }

      if ($char === '[') {

        if (!$ARGS_DONE) {
          $src .= $char;
          throw new \Exception("$src <--- Parse error on line {$this->line_number}: invalid syntax");
        }

        try {
          $options = $this->getContentUpToDelimiter(']');
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $src .= '[' . $options . ']';

        break;
      }

      if ($char === '{' ) {
 
        try {
          $content = $this->getCommandContent();
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $src .= '{' . $content . '}';

        $ARGS_DONE = true;
                  
      }

    }

    return new Token([
      'type' => $type ?? $this->command_name,
      'command_name' => $this->command_name,
      'command_content' => $content,
      'command_options' => $options,
      'command_src' => $src,
      'line_number' => $this->line_number,
    ]);
  }

  private function tokenizeCmdwithOptions(string|null $type=null) : Token
  {
    $options = '';
    $src = '\\' . $this->command_name;

    $ALLOWED_CHARS = [' ', '['];

    while (!is_null($char = $this->getChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $this->cursor--;
        break;
      }

      if ($char === ' ') {
        $this->cursor++;
        continue;
      }

      if ($char === '[') {

        try {
          $options = $this->getContentUpToDelimiter(']');
        } catch (\Exception $e) {
          die($e->getMessage());
        }

        $src .= '[' . $options . ']';

        break;
      }

    }

    return new Token([
      'type' => $this->getCommandType($this->command_name),
      'command_name' => $this->command_name,
      'command_content' => '',
      'command_options' => $options,
      'command_src' => $src,
      'line_number' => $this->line_number,
    ]);
  }
  
  private function getCommandContent()
  {

    try {
      $this->consumeSpaceUntilTarget('{');
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    $content = '';
    
    $brace_count = 1;

    $char = $this->getNextChar();

    while (!is_null($char) && $brace_count > 0) {

      if ($char === "\n" && $this->prev_char === "\n") {
        $so_far = '\\' . $this->command_name . '{' . $content;
        throw new \Exception("$so_far __ <--- Parse error on line {$this->line_number}: invalid syntax");
      }

      if ($char !== '}') {

        $content .= $char;
        
        if ($char === '{') $brace_count++;

        $char = $this->getNextChar();

      }
      else
      {

        $brace_count--;

        if ($brace_count > 0) {
          $content .= '}';              
          $char = $this->getNextChar();
        }

      }

    }

    if ($this->cursor === $this->num_chars) {
      $so_far = '\\' . $this->command_name . '{' . $content;
      throw new \Exception("$so_far __ <--- Missing closing brace } on line {$this->line_number}");
    }
    
    return $content;
    
  }

  /**
   * Get the content between two delimiters
   * Nesting not allowed.
   * 
   * The cursor should be just before $left delimiter
   * White space is ignored and is the only character allowed
   * before the $left delimiter, otherwise we break out of the loop
   */
  private function getContentBetweenDelimiters($left_delim, $right_delim)
  {
    $content = '';
    $ALLOWED_CHARS = [' ', $left_delim];
    
    while (!is_null($char = $this->getNextChar())) {

      if (!in_array($char, $ALLOWED_CHARS)) {
        $this->cursor--;
        break;
      }

      if ($char === ' ') {
        $this->cursor++;
        continue;
      }

      if ($char === $left_delim) {

        try {
          $content = $this->getContentUpToDelimiter($right_delim);
        } catch (\Exception $e) {
          die($e->getMessage());
        }
        
        break;
      }

    }

    return $content;
  }

  /**
   * Get the content up to the next delimiter
   * 
   * 
   * The cursor should be just before the delimiter
   * The only character not allowed before the 
   * delimiter is \n
   */
  private function getContentUpToDelimiter($delimiter)
  {    
    $content = '';

    $char = $this->getNextChar();

    while (!is_null($char) && $char !== $delimiter) {

      if ($char === "\n") {
        throw new \Exception("$content <--- Parse error on line {$this->line_number}: newline invalid syntax");
      }

      $content .= $char;      
      $char = $this->getNextChar();

    }

    if ($this->cursor === $this->num_chars) {      
      throw new \Exception("$content <--- Parse error on line {$this->line_number}: missing $delimiter");
    }

    return $content;

  }
  
  private function getEnvName() {
        
    try {
      $this->consumeSpaceUntilTarget('{');
    } catch (\Exception $e) {
      die($e->getMessage() . " in environment declaration");
    }
    
    $env = '';
    
    $char = $this->getNextChar();

    while (!is_null($char) && $char !== '}') {
 
      if (!(ctype_alpha($char) || $char === '*')) {
        throw new \Exception($env . $char . " <--- Invalid environment name at line {$this->line_number}");
      }

      $env .= $char;
      $char = $this->getNextChar();

    }

    if ($this->cursor === $this->num_chars) {
      throw new \Exception("Expected } at line {$this->line_number}");
    }
    
    return $env;
  
  }

  private function consumeSpaceUntilTarget($target) {
          
    if ($this->cursor === $this->num_chars) {
      throw new \Exception("Unexpected end of file on line {$this->line_number}");
    }

    $char = $this->getChar();
      
    while (!is_null($char) && $char === ' ') {
      $char = $this->getNextChar();      
    }

    if ($char === $target) {
      return;
    }
    
    throw new \Exception("Parse error: missing $target on line {$this->line_number}");
  
  }

  private function consumeUntilNonAlpha($from_cursor=true)
  {
    
    $char = $from_cursor ? $this->getChar() : $this->getNextChar();

    $alpha_text = '';

    while (!is_null($char) && ctype_alpha($char)) {
      $alpha_text .= $char;
      $char = $this->getNextChar();
    }
    
    return $alpha_text;
      
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
