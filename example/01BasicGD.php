<?php
use Naomai\PHPLayers as GDW;
if(!isset($gdwExample)) {header("Location: Example.php"); exit;}

echo "<h3>1. Basic GD2 functionality</h1>\n";

//create new image with size 500x320
$linesImg = new GDW\Image(500, 320);
//grab main layer (background)
$mainLayer = $linesImg->getLayerById(0);
$mainLayer->clear();

// set line thickness for all further shapes
$mainLayer->paint->lineSize = 3;
// draw line from point(10,10) to point(200,10) with red color
$mainLayer->paint->line(10, 10, 200, 10, 0xFF0000);
// set all further lines to green
$mainLayer->paint->lineColor = 0x008000;
$mainLayer->paint->line(10, 30, 200, 30);
$mainLayer->paint->line(10, 40, 180, 40);
// this one is still blue
$mainLayer->paint->line(250, 0, 250, 500, 0x008080);

// a rectangle with only border
$mainLayer->paint->rectangle(70, 5, 120, 55, GDRECT_BORDER, 0x660066);
$mainLayer->paint->lineSize = 2;
// now with border and fill
$mainLayer->paint->rectangle(80, 60, 130, 110, GDRECT_BORDER | GDRECT_FILLED, 0xFF0000, 0xCCFF00);

// TEXT
$mainLayer->paint->text(180, 110, "An example of text with default font", array('color'=>0xFF8080));
// aligning AKA anchoring
$mainLayer->paint->text(250, 130, "aligned left", array('color'=>0x808080, 'align' => GDALIGN_LEFT));
$mainLayer->paint->text(250, 140, "aligned center", array('color'=>0x808080, 'align' => GDALIGN_CENTER));
$mainLayer->paint->text(250, 150, "aligned right", array('color'=>0x808080, 'align' => GDALIGN_RIGHT));

// alpha blending
$mainLayer->paint->rectangle(180, 180, 320, 240, GDRECT_FILLED, GDCOLOR_DEFAULT, 0x994400);
$mainLayer->paint->text(250, 190, "Without alpha blending, the text is not composed prettily", array('color'=>0xAAAA00, 'align' => GDALIGN_CENTER));
$mainLayer->paint->alphaBlend = true;
$mainLayer->paint->text(250, 210, "Let's turn it on", array('color'=>0xAAAA00, 'align' => GDALIGN_CENTER, 'size' => 16));

// guide text (bitmap font)
$mainLayer->paint->textBM(260, 5, "Everything is drawn on a");
$mainLayer->paint->textBM(260, 15, "single layer, like drawing", 2);
$mainLayer->paint->textBM(260, 25, "on a sheet of paper", 3, 0x0000FF);
$mainLayer->paint->textBM(260, 45, "See Example 2 for more advanced");
$mainLayer->paint->textBM(260, 55, "usage.");

$dataUrl = $linesImg->getDataUrlPNG();
echo "<img src=\"".htmlspecialchars($dataUrl)."\"/>";

unset($linesImg, $mainLayer, $dataUrl);