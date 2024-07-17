<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Naomai\PHPLayers\Image;
use Naomai\PHPLayers\Layer;

require_once 'src/Image.php';

/**
 * @coversNothing
 */
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
        imagefilledrectangle(
            image: $layerFg->getGDHandle(),
            x1: 5, y1: 25, x2: 95, y2: 45,
            color: 0x3F0000FF
        );
        $mergedGd = $imageObj->getMergedGD();

        // unmodified bars from createTestImageObj
        $this->assertSameColorAt($mergedGd, 0xFF0000, 0, 0, 0);
        $this->assertSameColorAt($mergedGd, 0x00FF00, 20, 0, 0);
        // edge 
        $this->assertSameColorAt($mergedGd, 0x00FF00, 39, 0, 0);
        $this->assertSameColorAt($mergedGd, 0x0037FF, 40, 0, 0);
        // alpha
        $this->assertSameColorAt($mergedGd, 0x4F83FA12, 60, 0, 0);
        $this->assertSameColorAt($mergedGd, 0x7F000000, 81, 0, 0);

        // overpainted
        $this->assertSameColorAt($mergedGd, 0x7F0080, 5, 25);
        $this->assertSameColorAt($mergedGd, 0x007F80, 20, 25);
        $this->assertSameColorAt($mergedGd, 0x001BFF, 40, 25);
        $this->assertSameColorAt($mergedGd, 0x272344BE, 60, 25);
        $this->assertSameColorAt($mergedGd, 0x3F0000FF, 81, 25);
        // edges - todo take this to painttools test
        /*
        $this->assertDifferentColorAt($mergedGd, 0x7F0080, 4, 25);
        $this->assertDifferentColorAt($mergedGd, 0x7F0080, 5, 24);
        $this->assertSameColorAt($mergedGd, 0x3F0000FF, 94, 25);
        $this->assertSameColorAt($mergedGd, 0x3F0000FF, 95, 25);
        $this->assertDifferentColorAt($mergedGd, 0x3F0000FF, 95, 25);
        $this->assertSameColorAt($mergedGd, 0x3F0000FF, 81, 44);
        $this->assertSameColorAt($mergedGd, 0x3F0000FF, 81, 45);
        $this->assertDifferentColorAt($mergedGd, 0x3F0000FF, 81, 46);
        */
    }

    private function assertSameColorAt(\GdImage $img, int $colorExpected, int $x, int $y, int $tolerance = 2) {
        $colorActual = imagecolorat($img, $x, $y);
        $colorDiff = static::compareColors($colorExpected, $colorActual);
        $this->assertTrue(
            $colorDiff <= $tolerance,
            sprintf(
                "Failed asserting that color #%06x is close to #%06x within tolerance %d", 
                $colorActual, $colorExpected, $tolerance
            )
        );
    }

    private function assertDifferentColorAt(\GdImage $img, int $colorExpected, int $x, int $y, int $tolerance = 2) {
        $colorActual = imagecolorat($img, $x, $y);
        $colorDiff = static::compareColors($colorExpected, $colorActual);
        $this->assertFalse(
            $colorDiff <= $tolerance,
            sprintf(
                "Failed asserting that color #%06x is different to #%06x within tolerance %d", 
                $colorActual, $colorExpected, $tolerance
            )
        );
    }

    private function assertSameAlphaAt(\GdImage $img, int $colorExpected, int $x, int $y){
        $colorActual = imagecolorat($img, $x, $y);

        if($colorExpected & 0x7F == 0x7F) {
            $colorExpected &= 0x7F;
            $colorActual &= 0x7F;
        }
        $this->assertEquals($colorExpected, $colorActual);
    }

    private static function compareColors(int $color1, int $color2) : int {
        $aDiff = abs((($color1 & 0x7F000000) - ($color2 & 0x7F000000))>>24);
        $rDiff = abs((($color1 & 0xFF0000) - ($color2 & 0xFF0000))>>16);
        $gDiff = abs((($color1 & 0xFF00) - ($color2 & 0xFF00))>>8);
        $bDiff = abs((($color1 & 0xFF) - ($color2 & 0xFF)));
        
        $maxDiff = max($aDiff, $rDiff, $gDiff, $bDiff);
        return $maxDiff;
    }


    private static function createTestImageObj() : Image {
        $gdImage = imagecreatetruecolor(100, 50);
        imagealphablending($gdImage, false);
        imagefill($gdImage, 0, 0, 0x7F000000);
        
        imagefilledrectangle($gdImage,  0, 0, 20, 50, 0xFF0000);
        // this should overwrite red rectangle at column 20
        imagefilledrectangle($gdImage, 20, 0, 40, 50, 0x00FF00);
        imagefilledrectangle($gdImage, 40, 0, 60, 50, 0x0037FF);
        imagefilledrectangle($gdImage, 60, 0, 80, 50, 0x4F83FA12);

        $imageObj = Image::createFromGD($gdImage);
        return $imageObj;
    }
}