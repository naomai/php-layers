# Documentation
## Classes
The functionality of PHPLayers is split between various classes. Here is a brief explaination of their responsibilites.

- [Image](#image) - represents canvas of given dimensions, contains a stack of layers
  and recipe for preparing the result.
- [Layer](#layer) - like a transparent sheet, can be painted and 
  overlaid on top of other sheets. 
- LayerStack - a collection of layers that can be freely reordered
- Selection - fragment of a layer, limited by given dimensions, that
  can be moved or modified independently of the layer.
- Clip - a piece of layer, created (copied) from Selection, that can be
  freely pasted on the layer.
- Painter - provides basic tools for painting on the layer
- Composers - perform actual composing of layers. The extensive nature
  of this class allows to freely describe how to create an image
  from the layers.
- Generators - create more complex content, exposing simpler API to the user. The generation is done during the final composing.
  - Generators\RichText - formatted block of text
  - Generators\NonOverlappingText - generates text labels that never overlap. This leverages the "generation during composing" feature, to figure out correct placement after all the labels had been created.
- Filters - apply effects to the layer
  - Filters\PHPFilters - a collection of effects provided by GD2 library
  - Filters\ScrambleFilter - simulates line-scrambling of old
    analog TV encryption, Nagravision-Syster 
- Helpers - additional utility classes
  - Helpers\ImageExporter - functionality for exporting images
  - Helpers\LayerReorderCall - provides methods for changing position of a Layer in Layer Stack.


For readability, every class under PHPLayers namespace is referred using only its short name. 
This is equivalent to placing `namespace Naomai\PHPLayers;` in the code.

For example:

- `Image` refers to fully-qualified name `Naomai\PHPLayers\Image`
- `Generators\NonOverlappingText` refers to `Naomai\PHPLayers\Generators\NonOverlappingText`
- `\InvalidArgumentException` is the builtin PHP class in global namespace

# Image
`Image` is the main class, containing all layers, properties and 
settings on how to compose the final image.

## Create new image
`Image` can be instantiated using three ways:

### Empty image with dimensions
`Image::__construct(int $width, int $height, $createLayer = true)` 

Create new image with given dimensions.
- `width`, `height` - dimensions in pixels, must be greater than 0
- `createLayer` - if true, a background layer is automatically created. 
This layer is fully transparent.
- **Throws** `\InvalidArgumentException`: if image dimensions are invalid (<=0)

```php
$image = new Image(width: 50, height: 50, createLayer: true);
$background = $image->getLayerByIndex(0);
```

### createFromFile - import from file
`Image::createFromFile(string $fileName) : Image`

Import image file into new PHP Layers Image object. The new image contains
one layer with imported image as its content.

- `fileName` - path of existing image file
- **Returns** `Image` imported from file
- **Throws** `\RuntimeException`: if the file is not existing, or is not a valid image.


```php
$image = Image::createFromFile("olympic.jpg");
```

### createFromGD - import from PHP
`Image::createFromGD(\GdImage $gdHandle) : Image`

Wrap existing GD2 image into new PHP Layers Image object. The new image contains
one layer with its content **copied** from original image.

- `gdHandle` - the handle of source GD2 Image.
- **Returns** `Image` with contents of source `\GdImage`

```php
$gdImage = imagecreatefromjpeg("olympic.jpg");
$image = Image::createFromGD($gdImage);
```

## Exporting (saving)
Saving the result is possible through method `export()`. The method exposes
most common ways of delivering images. More detailed info can be found
in [Helpers\ImageExporter](#helpers-imageexporter) class.

### export - examples
```php
// send the image to browser directly, as JPEG
$image->export()->toBrowser(format: 'jpg'); 

// save on the disk
$image->export()->asFile(fileName: 'img/cherry.jpg');   

// create `data:...` URL for embedding in HTML
// omitting `format` exports image as PNG by default
$url = $image->export()->asDataUrl(); 

// raw binary data of image in WEBP format
$data = $image->export()->asBinaryData(format: 'webp');
```

### getMerged
`Image::getMerged() : Layer`

Finalize image into Layer object.

Merges all layers in image layer set using current layer composer.
The result is a new `Layer` object. The original layer set is left intact.

The new layer **is not attached** to the image. This means
you cannot use reordering functions on it.

- **Returns** Layer object containing merged content of image.

 
```php
$layerTwo = $image->getMerged();
```

### getMergedGD
`Image::getMergedGD() : \GdImage`

Finalize image into GD2 image handle.

Merges all layers in image layer set using current layer composer.
The result is a GD2 image handle accessible by native PHP functions.
The original layer set is left intact.

- **Returns** `\GdImage` handle containing merged content of image
 
```php
$layerTwo = $image->getMerged();
```

## Managing layers
PHPLayers makes use of a *Layer Stack*. This structure gives flexibility on
which layer goes on top of other. Now, the things going on the top
don't have to be drawn at the very end of script.

![Bottom layer is the lowest number](images/layerIndex.webp)

`LayerStack` is a sequence of layers, beginning at index 0 (bottom), 
then going up to the top. PHPLayers allows *reordering* of layers
after their creation. The *Layer index* is referring to order of the stack,
not the order of creating layers.

`Layer` is *attached* to the `Image`, when it is present on its Layer Stack.


### newLayer
`Image::newLayer(string $name=null) : Layer`

Create new layer and put it on top of layer stack. 

- `name` (optional) - label shown when image is rendered using ![Tiled Composer](#composers).
- **Returns** New `Layer` object attached to the `Image`

```php
$layerOne = $image->newLayer();
```

### getLayerByIndex
`Image::getLayerByIndex(int $id) : ?Layer`

Gets the Layer object from layer set using its index.

- `id` - a zero-based index of the layer in Layer Stack, counting from the bottom. 
If negative, count from the last layer.

  ![Negative indexes are like a copy of the positive part, where the imaginary last layer is 
ending on -1 position](images/getLayerByIndex.webp)
- **Returns** `Layer` object matching the index provided, or `null` if invalid.

```php
// $image has 3 layers
$layerBottom = $image->getLayerByIndex(0);
$layerTop = $image->getLayerByIndex(2);
$layerTop = $image->getLayerByIndex(-1); // same layer as above
$layerOutOfBounds = $image->getLayerByIndex(3); // null
```

### reorder
`Image::reorder(Layer $layerToMove) : Helpers\LayerReorderCall`

Access helper object for changing layer order on stack.

- `layerToMove` - a `Layer` to be relocated, **must** be attached to the `Image`
- **Returns** helper object providing reordering methods. The available methods
of such object can be found in 
[Helpers\LayerReorderCall](#helpers-layerreordercall) class.
- **Throws** `\RuntimeException`: when layer is not attached to the image

```php
$image->reorder($layerBottom)->putOver($layerTop);
```

### layerPutTop
`Image::layerPutTop(Layer $layerObj) : int`

Puts a layer object to the top of image's Layer Stack.

*Inserted layer is drawn over the existing image.*

This method also *attaches* layer to the image.
If the layer is already on the stack, it will be moved from its
previous place.


- `layerObj` - `Layer` to be put
- **Returns** New *Layer index* of the layer in Stack

```php
$image->layerPutTop($layerTwo);
```

### layerPutBottom
`Image::layerPutBottom(Layer $layerObj) : int`

Puts a layer object to the bottom of image's Layer Stack.

*Inserted layer is drawn behind the existing image.*

This method also *attaches* layer to the image.
If the layer is already on the stack, it will be moved from its
previous place.


- `layerObj` - `Layer` to be put
- **Returns** New *Layer index* of the layer in Stack

```php
$image->layerPutBottom($layerTwo);
```

### getLayerCount
`Image::getLayerCount() : int`

Get number of layers attached to the image.

- **Returns** number of layers

```php
$image = new Image(width: 100, height: 100);
$countLayers = $image->getLayerCount(); 
// $countLayers == 1
```


### getLayerStack
`Image::getLayerStack() : LayerStack`

Access the `LayerStack` of the image.

- **Returns** `LayerStack` object containing all the layers of image



# Layer
## Draw
### paint

`Layer::paint(...$options) : Painter`

Access `Painter` object, that provides functions for drawing on the layer. The object is
associated with the layer, and its settings are preserved between consecutive calls. 

- `options`: apply additional settings for this, and consecutive paint() calls. 
  See [Painter properties](#painter-properties) for possible arguments
- **Returns** `Painter` object associated with the layer

```php
// Draw square at position (30, 25)
$layer->paint()->rectangle(30, 25, 40, 35);
// Set color to red and thicker line size, draw again
$layer->paint(color: 0xFF0000, lineSize: 3)->rectangle(25, 20, 35, 30);
// Draw a line, this should also be thicker red
$layer->paint()->line(25, 20, 35, 30);

```
### fill
`Layer::fill(int $color) : void`

Replace entire layer content with given color. This function does not alpha-blend,
meaning it erases the previous layer content.

- `color`: an integer value of color in 0xAARRGGBB format

```php
// sunglasses effect
$layer = $image->newLayer("Shade");
$layer->fill(0x1A000000);
```

### clear
`Layer::clear() : void`

Clears layer buffer. The layer content is fully wiped, resulting in 
fully transparent surface. 

Effectively, it is equivalent to calling `fill()` with a transparent color.

```php
// if for some reason we want to discard entire layer content:
$layer->clear();
```
## Import into layer
### importFromFile
`Layer::importFromFile(string $fileName) : Layer`

Imports an image file into the layer. 
The imported image becomes new surface of layer, previous content 
is discarded.

- `fileName`: the path to the file to be imported
- **Returns**: current instance of the `Layer`. See [Method chaining](#method-chaining).
- **Throws**: `\RuntimeException` if either:
  - file does not exist
  - the format is not recognized, image is corrupted or inaccessible

### importFromGD
`Layer::importFromGD(\GdImage $gdSource) : Layer`

Imports a GD image resource into the layer. 
The imported image becomes new surface of layer, previous content 
is discarded.

- `gdSource`: the GD image to be imported
- **Returns**: current instance of the `Layer`. See [Method chaining](#method-chaining).

## Create selection
This functionality allows moving or modifying a limited fragment of layer surface.
The selection area can be set using three different methods. 
Further manipulation can be achieved using [Selection](#selection) helper object.

### select
`Layer::select(int $x, int $y, int $w, int $h) : Selection`

Create selection within provided dimensions

- `x`: Horizontal position of selection, relative to image
- `y`: Vertical position of selection, relative to image
- `w`: Width of selection
- `h`: Height of selection
- **Returns**: `Selection` Helper object for transforming selection

```php
// "pop" a small square by moving it a few pixels
$layer
  ->select(50, 60, 10, 10)
  ->moveOffset(-2, -2)
  ->apply();
```

### selectWhole
`Layer::selectWhole() : Selection`

Select entire area of layer buffer (image size). 
See [Buffer dimensions](#buffer-dimensions).

- **Returns**: `Selection` Helper object for transforming selection

```php
// shrink the image and move it to the center
$layer
  ->selectWhole()
  ->resize(50, 20)
  ->move(anchor: "center middle")
  ->apply();
```

### selectSurface
`Layer::selectSurface() : Selection`

Select Layer Surface area (imported content). 
See [Surface dimensions](#surface-dimensions).

- **Returns**: `Selection` Helper object for transforming selection

```php
// move watermark to the bottom right corner
$watermarkLayer
  ->selectSurface()
  ->move(anchor: "bottom right")
  ->apply();
```

## Change order
`Layer::reorder() : Helpers\LayerReorderCall`

Access helper object for changing this layer's position on stack. The layer 
**must be attached** to an image.

- **Returns** helper object providing reordering methods. The available methods
of such object can be found in 
[Helpers\LayerReorderCall](#helpers-layerreordercall) class.
- **Throws** `\RuntimeException`: when layer is not attached to the image

```php
$layer->reorder()->putOver($layerTop);
```

## Layer properties
### Opacity
Opacity is a level of coverage of the layer in percent, where 0% is completely transparent, and 100% is full coverage.  

`Layer::setOpacity(float $opacity) : void`

Set opacity of layer.

- `opacity`: opacity in percent

----

`Layer::getOpacity() : float`

Get opacity of layer in percent

- **Returns** opacity in percent

### Dimensions
Dimensions are defining position, and size of a box, expressed in a form of array:

```php
[
  'x'=>..., 'y'=>...,
  'w'=>..., 'h'=>...
]
```

#### Buffer dimensions
Size of the internal layer buffer. When the layer is attached to image, 
its dimensions become the same as of the images.

`Layer::getDimensions() : array`

Get dimensions and position of internal layer buffer.
- **Returns** array with dimensions of layer buffer.

#### Surface dimensions
When importing image from file into a new layer, its size is preserved as
*surface dimensions*. PHP-Layers uses it to calculate relative positioning,
allowing anchoring the content to the edges without much effort:
```php
$watermarkLayer
    ->selectSurface() // <- selection is made only within surface dimensions
    ->move(anchor: "bottom right")
    ->apply();
```

`Layer::getSurfaceDimensions() : array`

Get dimensions and position of layer surface.
- **Returns** array with dimensions of layer surface.

### Generator
Generator object makes it possible to modify layer content at the moment
of composing the final image. See [Generators](#generators).

`Layer::setGenerator(Generators\ILayerGenerator $generator) : void`

Attach a layer generator

- `generator`: object implementing `Generators\ILayerGenerator`


