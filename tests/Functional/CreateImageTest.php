<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Naomai\PHPLayers\Image;
use Naomai\PHPLayers\Layer;
use Naomai\PHPLayers\Test\Assert as ImageAssert;

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
        ImageAssert::assertSameColorAt($mergedGd, 0xFF0000, 0, 0, 0);
        ImageAssert::assertSameColorAt($mergedGd, 0x00FF00, 20, 0, 0);
        // edge 
        ImageAssert::assertSameColorAt($mergedGd, 0x00FF00, 39, 0, 0);
        ImageAssert::assertSameColorAt($mergedGd, 0x0037FF, 40, 0, 0);
        // alpha
        ImageAssert::assertSameColorAt($mergedGd, 0x4F83FA12, 60, 0, 0);
        ImageAssert::assertSameColorAt($mergedGd, 0x7F000000, 81, 0, 0);

        // overpainted
        ImageAssert::assertSameColorAt($mergedGd, 0x7F0080, 5, 25);
        ImageAssert::assertSameColorAt($mergedGd, 0x007F80, 20, 25);
        ImageAssert::assertSameColorAt($mergedGd, 0x001BFF, 40, 25);
        ImageAssert::assertSameColorAt($mergedGd, 0x272344BE, 60, 25);
        ImageAssert::assertSameColorAt($mergedGd, 0x3F0000FF, 81, 25);
        // edges - todo take this to painttools test
        /*
        ImageAssert::assertDifferentColorAt($mergedGd, 0x7F0080, 4, 25);
        ImageAssert::assertDifferentColorAt($mergedGd, 0x7F0080, 5, 24);
        ImageAssert::assertSameColorAt($mergedGd, 0x3F0000FF, 94, 25);
        ImageAssert::assertSameColorAt($mergedGd, 0x3F0000FF, 95, 25);
        ImageAssert::assertDifferentColorAt($mergedGd, 0x3F0000FF, 95, 25);
        ImageAssert::assertSameColorAt($mergedGd, 0x3F0000FF, 81, 44);
        ImageAssert::assertSameColorAt($mergedGd, 0x3F0000FF, 81, 45);
        ImageAssert::assertDifferentColorAt($mergedGd, 0x3F0000FF, 81, 46);
        */
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