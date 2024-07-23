# PHP Layers

PHP-Layers is a OOP library for creating images in PHP. 

It adds powerful layering functionality known from image editors, while staying intuitive and simple to use. 

Features:
- Stacking of images with transparency 
- Importing and exporting images, supports classic and modern formats (PNG, JPEG, WebP, AVIF)
- Generating Data URL for embedding in HTML file
- Convinience functions for drawing
- Rendering of rich text with word wrapping

**The project should be considered unstable for now, as I'm refactoring the code from an unhinged mess I made 10 years ago.**

## Example
Create a heavily outdated meme with just a few lines of code.

![Einstein with thug life glasses, watermarked](example/LayeringDemoResult.jpg)

```php
use Naomai\PHPLayers\Image;

// import image as background
$layersImg = Image::createFromFile("eins.jpg");

// create a watermark layer from file, and move it to bottom left corner
$watermarkLayer = $layersImg->newLayer()->importFromFile("cheesymemz.png");
$watermarkLayer
    ->selectSurface()
    ->move(x: 0, y: Image::IMAGE_BOTTOM)
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
The image is made of 3 layers, including the background. If we add an extra line, we can show all the layers as a split view:

```php
// TiledComposer is putting all layers in a grid, instead of merging them
$layersImg->setComposer(new PHPLayers\Composers\TiledComposer());

$dataUrl = $layersImg->export()->asDataUrl("webp");
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";
```

![Tiled view of indivitual layers making the Einstein thug life meme](example/LayeringDemoTiles.png)


## Use cases

Previously developed as GDWrapper, was powering a rendering engine for
Map Previews in Unreal Tournament Stats Tracker:

![Wireframe rendering of a game map](example/UTTDemo.jpg)

## Text features
PHP Layers includes two additional methods for adding text to your images. 

**Those features need heavy refactoring, and will be documented when they become stable.**

### Rich text
![Paragraphs of text, as rendered by library](example/TextDemoRT.png)

Capability to render formatted blocks of text. Change font, make it bold, mark it red in the middle of paragraph. 
We take care of the text flow. 

### Non-overlapping text
![Comparison of word "clutter" over illegible overpainted text vs spaced out words "space", as rendered by library](example/TextDemoNOText.png)
Spread different text labels, so they don't overlap. Useful for auto-generated images with tooltips.


