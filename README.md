# texstacks
Takes .tex file path and converts to HTML.

## How to use
```
<?php

use TexStacks\LatexArticleController;

$filepath = "myfiles/myarticles/myarticle.tex";

$article = new LatexArticleController($filepath);

$body = $article->convert();

$math_macros = $article->getMathMacros();

$front_matter = $article->getFrontMatter();

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