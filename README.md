# texstacks
Takes .tex/.aux source strings and converts to HTML.
String .aux is optional but needed for equation references.
See the samples/ folder for an example LaTeX source.

## How to use
```php
<?php

use TexStacks\LatexTransformer;

$tex_path = "myfiles/myarticles/myarticle.tex";
$aux_path = "myfiles/myarticles/myarticle.aux";

$tex_src = file_get_contents($tex_path);
$aux_src = file_get_contents($aux_path)

$tr = new LatexTransformer($tex_src, $aux_src);

$body = $tr->transform();

$math_macros = $tr->getMathMacros();

$front_matter = $tr->getFrontMatter();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>My Article</title>
  <script>
    window.MathJax = {
      loader: {load: ['ui/lazy', '[tex]/mathtools']},
      tex: {        
        packages: {
          '[+]': ['mathtools'],          
        }
        }
      };    
  </script>
  <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<body>
  <div style="text-align:center">
    <h1><?= $front_matter['title'] ?></h1>
    <div style="display:flex;justify-content:space-around;max-width:800px;margin:0 auto;font-size:1.25em">
      <?php foreach ($front_matter['authors'] ?? [] as $author) : ?>
        <span><?= $author->name ?></span>
      <?php endforeach; ?>
    </div>
    <div style="margin:1em 0"><?= $front_matter['date'] ?></div>
  </div>

  <div style="display:none">
    \[
    <?= $math_macros  ?>
    \]
  </div>

  <main>
    <?= $body ?>
    <?php if (isset($front_matter['thanks'])) : ?>
    <hr style="margin-top:2em">
      <?php foreach ($front_matter['thanks'] as $thanks) : ?>
        <div><?= $thanks ?></div>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>
  
</body>
</html>
```