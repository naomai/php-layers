<?php
use Naomai\PHPLayers;
use Naomai\PHPLayers\Image;
if(!isset($gdwExample)) {header("Location: Example.php"); exit;}

echo "<h3>4. Watermark</h1>\n";

// import image as background
$layersImg = Image::createFromFile(__DIR__ . "/eins.jpg");

// create watermark layer
$watermarkLayer = $layersImg->newLayer()->importFromFile(__DIR__ . "/cheesymemz.png");

// move the watermark to bottom left corner.
// 1. make selection (like CTRL+A)
// 2. then drag the contents, 
// 3. finally apply the result.
$watermarkLayer
    ->selectSurface()
    ->move(0, Image::IMAGE_BOTTOM)
    ->apply();


// make things more THUG
$thugLayer = $layersImg->newLayer()->importFromFile(__DIR__ . "/thug.png");

// moving to a fixed position
$thugLayer
    ->selectSurface()
    ->move(290, 95)
    ->apply();

// export the image as data URL
$dataUrl = $layersImg->getDataUrlPNG();
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";

// tiled view of layers
echo "Separate view of different layers:<br/>";
$layersImg->setComposer(new PHPLayers\Composers\TiledComposer());
$dataUrl = $layersImg->getDataUrlPNG();
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";

unset($watermarkLayer, $layersImg);