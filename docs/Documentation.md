# Documentation
For readability, every class under PHPLayers namespace is referred using only its name. 
This is equivalent to placing `use Naomai\PHPLayers;` in the code.

For example:

- `Image` refers to fully-qualified name `Naomai\PHPLayers\Image`
- `Generators\NonOverlappingText` refers to `Naomai\PHPLayers\Generators\NonOverlappingText`
- `\InvalidArgumentException` is the builtin PHP class in global namespace

# Image
`Image` is the main class, containing all layers, properties and 
settings on how to compose the final image.

## Creating/opening
`Image` can be instantiated using three ways:

### Constructor with size
`Image::__construct(int $width, int $height, $createLayer = true)` 

Create new image with given dimensions.
- `width`, `height` - dimensions in pixels
- `createLayer` - if true, a background layer is automatically created. 
This layer is fully transparent.

```php
$image = new Image(width: 50, height: 50, createLayer: true);
$background = $image->getLayerByIndex(0);
```

### createFromFile
`Image::createFromFile(string $fileName) : Image`

Import image file into new PHP Layers Image object. The new image contains
one layer with imported image as its content.

- `fileName` - path of existing image file

`\RuntimeException` is thrown, if the file is not existing, or is not a valid image.

**Returns** `Image` imported from file

```php
$image = Image::createFromFile("olympic.jpg");
```

### createFromGD
`Image::createFromGD(\GdImage $gdHandle) : Image`

Wrap existing GD2 image into new PHP Layers Image object. The new image contains
one layer with its content **copied** from original image.

- `gdHandle` - the handle of source GD2 Image.

**Returns** `Image` with contents of source `\GdImage`

```php
$gdImage = imagecreatefromjpeg("olympic.jpg");
$image = Image::createFromGD($gdImage);
```

## Exporting (saving)
Saving the result is possible through method `export()`. The method exposes
most common ways of delivering images. More detailed info can be found
in [Helpers\ImageExporter](#helpers-imageexporter) class.

### Examples
```php
// send the image to browser directly, as JPEG
$image->export()->toBrowser(format: 'jpg'); 

// save on the disk
$image->export()->asFile(fileName: 'img/cherry.png', format: 'png');   

// create `data:...` URL for embedding in HTML
// omitting `format` exports image as PNG by default
$url = $image->export()->asDataUrl(); 

// raw binary data of image in WEBP format
$data = $image->export()->asBinaryData(format: 'webp');
```

## Managing layers
### newLayer
`Image::newLayer(string $name=null) : Layer`

Create new layer and put it on top of layer set. 

- `name` (optional) - passed to internal `Layer::name` property that can be used
in alternate layer composers, such as `Composers\TiledComposer`.

**Returns** New `Layer` object attached to the `Image`

```php
$layerOne = $image->newLayer();
```

### getLayerByIndex
`Image::getLayerByIndex(int $id) : ?Layer`

Gets the Layer object from layer set using its index.

- `id` - a zero-based index of the layer in Layer Stack, counting from the bottom. 
If negative, count from the last layer.

!(Negative indexes are like a copy of the positive part, where the imaginary last layer is 
ending on -1 position)[getLayerByIndex.webp]

**Returns** `Layer` object matching the index provided, or `null` if invalid.

```php
// $image has 3 layers
$layerBottom = $image->getLayerByIndex(0);
$layerTop = $image->getLayerByIndex(2);
$layerTop = $image->getLayerByIndex(-1); // same layer as above
$layerOutOfBounds = $image->getLayerByIndex(3); // null
```
