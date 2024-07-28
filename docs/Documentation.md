# Documentation
For readability, every class under PHPLayers namespace is referred using only its name. This is equivalent to placing `use Naomai\PHPLayers;` in the code.

For example:

- `Image` refers to fully-qualified name `Naomai\PHPLayers\Image`
- `Generators\NonOverlappingText` refers to `Naomai\PHPLayers\Generators\NonOverlappingText`
- `\InvalidArgumentException` is the builtin PHP class in global namespace

## Image
`Image` is the main class, holding everything needed to render the final image.

### Creation
`Image` can be instantiated using three ways:

#### Constructor with size
```php
Image::__construct(int $width, int $height, $createLayer = true)
```
Create new image with given dimensions.

If `createLayer` is true, a background layer is automatically created. This layer is fully transparent.

```php
$image = new Image(width: 50, height: 50, createLayer: true);
$background = $image->getLayerByIndex(0);
```

#### createFromFile
```php
Image::createFromFile(string $fileName) : Image
```
Import image file into new PHP Layers Image object. The new image contains one layer with imported image as its content.

`\RuntimeException` is thrown, if the file is not existing, or is not a valid image.

**Returns** `Image` imported from file

```php
$image = Image::createFromFile("olympic.jpg");
$background = $image->getLayerByIndex(0);
```

#### createFromGD
```php
Image::createFromGD(\GdImage $gdHandle) : Image
```
Wrap existing GD image into new PHP Layers Image object. The new image contains one layer with 
its content **copied** from original image.

```php
$gdImage = imagecreatefromjpeg("olympic.jpg");
$image = Image::createFromGD($gdImage);
$background = $image->getLayerByIndex(0);
```

### Managing layers
#### newLayer
```php
Image::newLayer(string $name=null) : Layer
```
Create new layer and put it on top of layer set. 

Optional `name` is passed to internal `Layer::name` property that can be used in alternate layer composers, such as `Composers\TiledComposer`.

```php
$layerOne = $image->newLayer();
```

#### 
```php
Image::getLayerByIndex(int $id) : ?Layer
```

Gets the Layer object from layer set using its index.

`id` is an index of the layer in Layer Stack. If negative, count from the last layer.

**Returns** `Layer` object matching the index provided, or null if invalid.