# PHP Layers

This library allows to easily create layered images using PHP and GD library. 
Previously developed as GDWrapper, was powering a rendering engine for
Map Previews in Unreal Tournament Stats Tracker:

![Wireframe rendering of a game map](example/UTTDemo.jpg)

## Example: adding watermark to photo

![Picture of a trolley, with added watermark](example/WatermarkDemo.jpg)

```
// import image as background
$layersImg = GDW\Image::createFromFile(__DIR__ . "/stroller.jpg");

// add watermark layer
$watermarkLayer = GDW\Layer::createFromFile(__DIR__ . "/lg_watermark.png");
// naming is optional
$watermarkLayer->name = "Watermark layer";
// let's put the watermark layer on top of the image
$layersImg->addLayerTop($watermarkLayer);

// we select (like CTRL+A), then drag the contents, and finally apply the result.
$watermarkLayer->select()->move(0, GDW\IMAGE_BOTTOM)->apply();

// export the image as data URL
$dataUrl = $layersImg->getDataUrlPNG();
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";
```

## Example: rich text
The library has capability to render formatted blocks of text, and also non-overlapping text. This will be documented in future.

![Example of text rendering using the library](example/TextDemo.jpg)

