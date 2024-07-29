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
- `width` and `height` are in pixels
- If `createLayer` is true, a background layer is automatically created. 
This layer is fully transparent.

```php
$image = new Image(width: 50, height: 50, createLayer: true);
$background = $image->getLayerByIndex(0);
```

### createFromFile
`Image::createFromFile(string $fileName) : Image`

Import image file into new PHP Layers Image object. The new image contains
one layer with imported image as its content.

`\RuntimeException` is thrown, if the file is not existing, or is not a valid image.

**Returns** `Image` imported from file

```php
$image = Image::createFromFile("olympic.jpg");
$background = $image->getLayerByIndex(0);
```

### createFromGD
`Image::createFromGD(\GdImage $gdHandle) : Image`

Wrap existing GD2 image into new PHP Layers Image object. The new image contains
one layer with its content **copied** from original image.

- `gdHandle` is the handle of source GD2 Image.

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

Optional `name` is passed to internal `Layer::name` property that can be used 
in alternate layer composers, such as `Composers\TiledComposer`.

**Returns** New `Layer` object attached to the `Image`

```php
$layerOne = $image->newLayer();
```

### getLayerByIndex
`Image::getLayerByIndex(int $id) : ?Layer`

Gets the Layer object from layer set using its index.

`id` is an index of the layer in Layer Stack. If negative, count from the last layer.

**Returns** `Layer` object matching the index provided, or null if invalid.