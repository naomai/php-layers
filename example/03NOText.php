<?php
use Naomai\PHPLayers as GDW;
if(!isset($gdwExample)) {header("Location: Example.php"); exit;}

echo "<h3>3. Text functionality</h1>\n";
echo "<h4>Non-overlapping text</h1>\n";

$exampleImg = new GDW\Image(400, 200);
$mainLayer = $exampleImg->getLayerByIndex(0);
$mainLayer->clear();

$textRenderer = new GDW\Generators\NonOverlappingText();
$mainLayer->setGenerator($textRenderer);

$mainLayer->paint()->alphaBlend = true;
$mainLayer->paint()->text(101, 9, "clutter", ['color'=>0x000000]);
$mainLayer->paint()->text(99, 13, "clutter", ['color'=>0x000000]);
$mainLayer->paint()->text(103, 12, "clutter", ['color'=>0x000000]);
$mainLayer->paint()->text(100, 9, "clutter", ['color'=>0xFF0000, 'size'=>18]);

$textRenderer->write(101, 69,"space", ['color'=>0x000000]);
$textRenderer->write(99, 73,"space", ['color'=>0x000000]);
$textRenderer->write(103, 72,"space", ['color'=>0x000000]);
$textRenderer->write(103, 71,"space", ['color'=>0x000000]);
$textRenderer->write(102, 69,"space", ['color'=>0xFF0000, 'size'=>18]);
$textRenderer->write(103, 71,"space", ['color'=>0x000000]);
$textRenderer->write(103, 71,"space", ['color'=>0x000000]);
$textRenderer->write(103, 72,"space", ['color'=>0x000000]);
$textRenderer->write(103, 71,"space", ['color'=>0x000000]);
$textRenderer->write(103, 71,"space", ['color'=>0x000000]);
$textRenderer->write(103, 72,"space", ['color'=>0x000000]);


$dataUrl = $exampleImg->getDataUrlPNG();
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";

unset($exampleImg, $mainLayer, $dataUrl);

echo "<h4>Rich text</h1>\n";

$exampleImg = new GDW\Image(500, 200);
$mainLayer = $exampleImg->getLayerByIndex(0);
$mainLayer->clear();

$text = new GDW\Generators\RichText();
$mainLayer->setGenerator($text);

$text->textColor = 0x002290;
$text->fontSize = 12;
$text->font = "Lato";
$text->fontBold = true;

$text->position=["x"=>0,"y"=>0,"width"=>500,"height"=>'auto'];
$text->margin=['left'=>8, 'right'=>8,'top'=>8,'bottom'=>8];

$text->write("This is a text document! ");

$text->newParagraph();
$text->fontSize = 9;
$text->fontBold = false;
$text->write("Cat food is food for consumption by cats. Cats have specific requirements for their dietary nutrients. ");
$text->textColor = 0x902200;
$text->write("Certain nutrients, including many ");
$text->textColor = 0xFF2200;
$text->fontBold = true;
$text->write("vitamins and amino acids");
$text->textColor = 0x902200;
$text->fontBold = false;
$text->write(", are degraded by the temperatures, pressures and chemical treatments used during manufacture, and hence must be added after manufacture to avoid nutritional deficiency.");

$dataUrl = $exampleImg->getDataUrlPNG();
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/><br/>";

unset($exampleImg, $mainLayer, $dataUrl);