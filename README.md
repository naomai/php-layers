# PHP-Layers
## About
PHP-Layers is a OOP library for creating images in PHP. 

It adds powerful layering functionality known from image editors, while staying intuitive and simple to use. 

- PHP 8.0+
- GD2 extension
- Stacking of images, with transparency
- Importing and exporting images - supports classic and modern formats (PNG, JPEG, WebP, AVIF). 
- Convinience functions for drawing
- Rich text

**The project should be considered unstable for now, as I'm refactoring the code from an unhinged mess I made 10 years ago.**

- [Getting started](docs/GettingStarted.md)
- [Documentation](docs/Documentation.md)

## Example
Here is a code adding watermark to an image:

```php
use Naomai\PHPLayers\Image;

// import image as background
$layersImg = Image::createFromFile("stroller.jpg");

// create a watermark layer from file, and move it to bottom left corner
$watermarkLayer = $layersImg->newLayer()->importFromFile("lg_watermark.png");
$watermarkLayer
    ->selectSurface()
    ->move(anchor: "bottom left")
    ->apply();

// export the image, and include it in the HTML file
$dataUrl = $layersImg->export()->asDataUrl("webp");
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";
```

## Use cases

Previously developed as GDWrapper, was powering a rendering engine for
Map Previews in Unreal Tournament Stats Tracker:

![Wireframe rendering of a game map](example/UTTDemo.jpg)

## Text features
Phancake includes two additional methods for adding text to your images. 

**Those features need heavy refactoring, and will be documented when they become stable.**

### Rich text
Capability to render formatted blocks of text. Change font, make it bold, mark it red in the middle of paragraph. 
We take care of the text flow. 

![Paragraphs of text, as rendered by library](example/TextDemoRT.png)

### Non-overlapping text
Spread different text labels, so they don't overlap. Useful for auto-generated images with tooltips.

![Tooltips with names of enemies](example/TextDemoNOText_Monsters.png)
