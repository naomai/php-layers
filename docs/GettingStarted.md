# Creating images
## Overview
The way we handle images in PHP is, honestly, really dated. Since the time of 
PHP/FI 2.0, which introduced GD image library in 1997, the language received 
support for objective programming. The support has matured quite a lot with 
recent PHP8 additions. Still, we're working on images using functions with 
long names, which at some point of time, became all-lowercase. 
Remember how we used to write ImageSetPixel instead of imagesetpixel?

The evolution of built-in GD library is out of scope here. Instead, 
we're trying to reduce the boilerplate to minimum, and tackle some common 
pitfalls:

- Why is my transparency gone?
  - We've enabled saving alpha channel by default. 
- Is my semi-transparent rectangle going to smoothly blend with an image, 
or maybe punch a hole in it because it overwrites pixels?
  - If you often need to switch between alpha blending modes 
  with `imagealphablending()`, there is a cleaner solution. You can use 
  two separate `paint()` sessions, with each one having separate setting 
  for `alphaBlending`.
- I tried to paste one PNG over other with 50% opacity. 
I used imagecopymerge, because they said it supports setting opacity, 
but now the pasted part has ugly black background.
  - True opacity control is a must in any image editor with layers. 
  We've implemented proper merging, it's just a matter of saying 
  `$layer->setOpacity(50)` in your code.

![Three emojis on a checkerboard background. A blueberry in black square, showing missing alpha channel. A hat that was pasted on top of persons's head, but its rectangle corrupted the image of head. A motion illusion of a unicorn, made by two fading copies of unicorn, background behind the effect is corrupted.](images/pitfalls.webp)

## Installation

`php-layers` can be installed by using Composer.

### Composer

`php-layers` is currently only installable from Git, adding repository 
to your composer.json file:

```json
    "repositories": [
        {
            "url": "https://github.com/naomai/php-layers.git",
            "type": "git"
        }
    ]
```

```shell
composer require naomai/php-layers
```

Standalone and Packagist methods will be added in the future.

### Test
You can test your installation with a simple example.
```php
<?php
use Naomai\PHPLayers;

require_once __DIR__ . "/vendor/autoload.php";

$testImage = new PHPLayers\Image(200, 50);
$layer = $testImage->newLayer();
$layer->paint()->text(x: 0, y: 0, text: "It Works!");
$testImage->export()->toBrowser();

```

## Basic examples
### Meme

Create a heavily outdated meme with just a few lines of code.

```php
use Naomai\PHPLayers\Image;

// import image as background
$layersImg = Image::createFromFile("eins.jpg");

// create a watermark layer from file, and move it to bottom left corner
$watermarkLayer = $layersImg->newLayer()->importFromFile("cheesymemz.png");
$watermarkLayer
    ->selectSurface()
    ->move(anchor: "bottom left")
    ->apply();

// make things more THUG
$thugLayer = $layersImg->newLayer()->importFromFile("thug.png");
$thugLayer
    ->selectSurface()
    ->move(x: 290, y: 95)
    ->apply();

// export the image, and include it in the HTML file
$dataUrl = $layersImg->export()->asDataUrl("webp");
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";
```

![Einstein with thug life glasses, watermarked](../example/LayeringDemoResult.jpg)


The image is made of 3 layers, including the background. If we add an 
extra line, we can show all the layers as a split view:

```php
// TiledComposer is putting all layers in a grid, instead of merging them
$layersImg->setComposer(new PHPLayers\Composers\TiledComposer());

$dataUrl = $layersImg->export()->asDataUrl("webp");
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";
```

![Tiled view of indivitual layers making the Einstein thug life meme](../example/LayeringDemoTiles.png)
