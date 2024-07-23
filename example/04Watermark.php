<?php
use Naomai\PHPLayers;
use Naomai\PHPLayers\Image;
if(!isset($gdwExample)) {header("Location: Example.php"); exit;}

echo "<h3>4. Watermark</h1>\n";

// import image as background
$layersImg = Image::createFromFile(__DIR__ . "/eins.jpg");

// create a watermark layer from file, and move it to bottom left corner
$watermarkLayer = $layersImg->newLayer()->importFromFile(__DIR__ . "/cheesymemz.png");
$watermarkLayer
    ->selectSurface()
    ->move(x: 0, y: Image::IMAGE_BOTTOM)
    ->apply();

// make things more THUG
$thugLayer = $layersImg->newLayer()->importFromFile(__DIR__ . "/thug.png");
$thugLayer
    ->selectSurface()
    ->move(x: 290, y: 95)
    ->apply();

// export the image, and include it in the HTML file
$dataUrl = $layersImg->export()->asDataUrl("webp");
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";

// tiled view of layers
echo "Separate view of different layers:<br/>";
$layersImg->setComposer(new PHPLayers\Composers\TiledComposer());
$dataUrl = $layersImg->export()->asDataUrl("png");
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";

unset($watermarkLayer, $layersImg);