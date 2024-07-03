<?php
use Naomai\PHPLayers as GDW;
if(!isset($gdwExample)) {header("Location: Example.php"); exit;}

echo "<h3>4. Watermark</h1>\n";

// import image as background
$layersImg = GDW\Image::createFromFile(__DIR__ . "/eins.jpg");

// create watermark layer
$watermarkLayer = GDW\Layer::createFromFile(__DIR__ . "/cheesymemz.png");

// attach the layer to image, so we can move things around
$layersImg->layerPutTop($watermarkLayer);
// we're going to move the watermark to bottom left corner
// make selection (like CTRL+A), then drag the contents, and finally apply the result.
$watermarkLayer->selectWhole()->move(0, GDW\IMAGE_BOTTOM)->apply();

// make things more THUG
$thugLayer = GDW\Layer::createFromFile(__DIR__ . "/thug.png");
$layersImg->layerPutTop($thugLayer);
// moving to a fixed position
$thugLayer->selectWhole()->move(290, 95)->apply();

// export the image as data URL
$dataUrl = $layersImg->getDataUrlPNG();
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";

// tiled view of layers
echo "Separate view of different layers:<br/>";
$layersImg->setComposer(new GDW\Composers\TiledComposer());
$dataUrl = $layersImg->getDataUrlPNG();
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";

unset($watermarkLayer, $layersImg);