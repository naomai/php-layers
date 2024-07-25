<?php
use Naomai\PHPLayers as GDW;
if(!isset($gdwExample)) {header("Location: Example.php"); exit;}



echo "<h3>2. Layers</h1>\n";

$layersImg = new GDW\Image(400, 230);
$mainLayer = $layersImg->getLayerByIndex(0);
$mainLayer->clear();

// create new layer with image from file
$catLayer = $layersImg->newLayer("Nasty cat")
    ->importFromFile(__DIR__ . "/neko.jpg");
// make it transparent
$catLayer->setOpacity(25);

// let's move to the background layer, this text will be hidden behind the cat-layer
$mainLayer->paint()->text(100, 90, "I ate your snack. Now, pet me ^^", ['color'=>0x00FFFF, 'size' => 16]);



// now let's draw on the cat-layer
$catLayer->paint()->alphaBlend = true;
// oops, this text will get clipped beyond original image dimensions
$catLayer->paint()->text(150, 155, "purrrrrrrrrrrrrrrrrrrrrrrrrrrrrr~ oh no, i broke", ['color'=>0xFF00FF, 'size' => 14]);

$catLayer->transformPermanently();
$catLayer->paint()->text(150, 175, "i hid a mesg frum u ^.^", ['color'=>0x0000FF, 'size' => 14]);




$tStart = microtime(true);
$dataUrl = $layersImg->export()->asDataUrl("png");
$tEnd = microtime(true);

printf("Tool %d ms", ($tEnd-$tStart)*1000);

echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";

$tStart = microtime(true);
$layersImg->getComposer()->gammaBlending = true;
$dataUrl = $layersImg->export()->asDataUrl("png");
$tEnd = microtime(true);

printf("Tool %d ms", ($tEnd-$tStart)*1000);

echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";

// tiled view of layers
echo "Separate view of different layers:<br/>";
$layersImg->setComposer(new GDW\Composers\TiledComposer());
$dataUrl = $layersImg->export()->asDataUrl("png");
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";


unset($layersImg, $mainLayer, $catLayer, $dataUrl);