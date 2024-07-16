<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Naomai\PHPLayers\Image;
use Naomai\PHPLayers\Layer;

require_once 'src/Image.php';

final class CreateImageTest extends TestCase {
    const TESTFILE_WIDTH = 50;
    const TESTFILE_HEIGHT = 55;

    public function testImportedImageProperties() {
        $imageObj = Image::createFromFile(__DIR__ . "/../TestImages/BasicSquares.png");

        $imageSize = $imageObj->getSize();
        $this->assertEquals(self::TESTFILE_WIDTH, $imageSize["w"]);
        $this->assertEquals(self::TESTFILE_HEIGHT, $imageSize["h"]);
    }

    public function testAlphaBlending() {
        $imageObj = static::createTestImageObj();
        $layerBg = $imageObj->getLayerByIndex(0);
        $layerFg = $imageObj->newLayer();
        $layerFg->paint->rectangle(
            x1: 0, y1: 25, x2: 100, y2: 50,
            colorFill: 0x3F0000FF,
            type: GDRECT_FILLED
        );
        $mergedGd = $imageObj->getMergedGD();

        $this->assertSameColorAt($mergedGd, 0xFF0000, 0, 0);
        $this->assertSameColorAt($mergedGd, 0x00FF00, 20, 0);
        $this->assertSameColorAt($mergedGd, 0x00FF00, 39, 0);
        $this->assertSameColorAt($mergedGd, 0x0037FF, 40, 0);
        $this->assertSameColorAt($mergedGd, 0x4F83FA12, 60, 0);
        $this->assertSameColorAt($mergedGd, 0x4F83FA12, 80, 0);
        $this->assertSameColorAt($mergedGd, 0x7F000000, 81, 0);

        $this->assertAlmostSameColorAt($mergedGd, 0x800080, 0, 25);
    }


    private function assertSameColorAt(\GdImage $img, int $colorExpected, int $x, int $y){
        $colorActual = imagecolorat($img, $x, $y);

        if($colorExpected & 0x7F == 0x7F) {
            $this->assertSameAlphaAt($img, $colorExpected, $x, $y);
            return;
        }
        $this->assertEquals($colorExpected, $colorActual);
    }

    private function assertAlmostSameColorAt(\GdImage $img, int $colorExpected, int $x, int $y, int $maxDistance = 4){
        $colorActual = imagecolorat($img, $x, $y);

        $aDiff = abs((($colorExpected & 0x7F000000) - ($colorActual & 0x7F000000))>>24);
        $rDiff = abs((($colorExpected & 0xFF0000) - ($colorActual & 0xFF0000))>>16);
        $gDiff = abs((($colorExpected & 0xFF00) - ($colorActual & 0xFF00))>>8);
        $bDiff = abs((($colorExpected & 0xFF) - ($colorActual & 0xFF)));
        
        $totalDiff = $aDiff + $rDiff + $gDiff + $bDiff;

        $this->assertTrue($totalDiff <= $maxDistance);
    }

    private function assertSameAlphaAt(\GdImage $img, int $colorExpected, int $x, int $y){
        $colorActual = imagecolorat($img, $x, $y);

        if($colorExpected & 0x7F == 0x7F) {
            $colorExpected &= 0x7F;
            $colorActual &= 0x7F;
        }
        $this->assertEquals($colorExpected, $colorActual);
    }


    private static function createTestImageObj() : Image {
        $gdImage = imagecreatetruecolor(100, 50);
        imagealphablending($gdImage, false);
        imagefill($gdImage, 0, 0, 0x7F000000);
        
        imagefilledrectangle($gdImage,  0, 0, 20, 50, 0xFF0000);
        imagefilledrectangle($gdImage, 20, 0, 40, 50, 0x00FF00);
        imagefilledrectangle($gdImage, 40, 0, 60, 50, 0x0037FF);
        imagefilledrectangle($gdImage, 60, 0, 80, 50, 0x4F83FA12);

        $imageObj = Image::createFromGD($gdImage);
        return $imageObj;
    }
}