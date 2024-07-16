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



}