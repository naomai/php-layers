<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Naomai\PHPLayers\Image;
use Naomai\PHPLayers\Layer;

require_once 'src/Image.php';

/**
 * @covers Naomai\PHPLayers\Image
 */
final class ImageTest extends TestCase {



    public function testCreateFromGD() : void {
        $gdSource = imagecreatetruecolor(50, 60);
        imagefill($gdSource, 0, 0, 0xFF0000);
        imagefilledrectangle($gdSource, 0, 0, 25, 25, 0x00FF00);

        $imageObj = Image::createFromGD($gdSource);
        $this->assertInstanceOf(Image::class, $imageObj);

    }

    public function testCreateFromFile() : void {
        $imageObj = Image::createFromFile(__DIR__ . "/../TestImages/BasicSquares.png");

        $this->assertInstanceOf(Image::class, $imageObj);
    }

    public function testGetSize() : void {
        $testImg = static::createEmptyImage();
        $size = $testImg->getSize();

        $this->assertEquals(['w'=>200, 'h'=>100], $size);
    }

    public function testSetSize() : void {
        $testImg = static::createEmptyImage();
        $testImg->setSize(75, 48);
        $size = $testImg->getSize();
        $this->assertEquals(['w'=>75, 'h'=>48], $size);
    }

    public function testGetLayerByIndex() : void {
        $testImg = static::createTwoLayerImage();
        $bg = $testImg->getLayerByIndex(0);
        $fg = $testImg->getLayerByIndex(1);

        $this->assertNotNull($bg);
        $this->assertNotNull($fg);
        $this->assertNotSame($bg, $fg);

        // negative index
        $last = $testImg->getLayerByIndex(-1);
        $this->assertSame($fg, $last);

        $underflow = $testImg->getLayerByIndex(-3);
        $this->assertNull($underflow);
        $overflow = $testImg->getLayerByIndex(2);
        $this->assertNull($overflow);
    }


    private static function createEmptyImage() : Image {
        $testImg = new Image(200, 100);
        return $testImg;
    }

    private static function createTwoLayerImage() : Image {
        $testImg = new Image(200, 100, false);
        $testImg->newLayer("banana");
        $testImg->newLayer("strawberry");
        return $testImg;
    }

}