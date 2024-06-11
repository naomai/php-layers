<?php
use Naomai\PHPLayers as GDW;
if(!isset($gdwExample)) {header("Location: Example.php"); exit;}

echo "<h3>4. Watermark</h1>\n";

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

unset($watermarkLayer, $layersImg);