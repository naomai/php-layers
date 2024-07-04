<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Naomai\PHPLayers\Image;
use Naomai\PHPLayers\Layer;

require_once 'src/Image.php';

final class ImageTest extends TestCase {
    const TESTFILE_WIDTH = 50;
    const TESTFILE_HEIGHT = 55;

    public function testCreateFromGD() : Image {
        $gdSource = imagecreatetruecolor(50, 60);
        imagefill($gdSource, 0, 0, 0xFF0000);
        imagefilledrectangle($gdSource, 0, 0, 25, 25, 0x00FF00);

        $imageObj = Image::createFromGD($gdSource);
        $this->assertInstanceOf(Image::class, $imageObj);

        $imageSize = $imageObj->getSize();
        $this->assertEquals(50,$imageSize["w"]);
        $this->assertEquals(60,$imageSize["h"]);
        
        return $imageObj;
    }

    public function testCreateFromFile() : Image {
        $imageObj = Image::createFromFile(__DIR__ . "/BasicSquares.png");

        $this->assertInstanceOf(Image::class, $imageObj);

        $imageSize = $imageObj->getSize();
        $this->assertEquals(self::TESTFILE_WIDTH, $imageSize["w"]);
        $this->assertEquals(self::TESTFILE_HEIGHT, $imageSize["h"]);
        
        return $imageObj;
    }

    #[Depends('testCreateFromFile')]
    public function testContentFromFile(Image $imageObj) : void {
        $mergedGd = $imageObj->getMergedGD();

        $this->assertTrue(is_object($mergedGd) && $mergedGd instanceof \GdImage);

        $pixelTestMin = imagecolorat($mergedGd, 0, 0);
        $pixelTestMax = imagecolorat($mergedGd, 24, 24);
        $this->assertEquals(0xFF0000, $pixelTestMin);
        $this->assertEquals(0xFF0000, $pixelTestMax);

        $pixelTestMin = imagecolorat($mergedGd, 25, 0);
        $pixelTestMax = imagecolorat($mergedGd, 49, 24);
        $this->assertEquals(0xFF6A00, $pixelTestMin);
        $this->assertEquals(0xFF6A00, $pixelTestMax);

        $pixelTransparency = imagecolorat($mergedGd, 49, 25);
        $this->assertEquals(0x3F00FFFF, $pixelTransparency); 
    }
    
    #[Depends('testCreateFromFile')]
    public function testImageSetSize(Image $imageObj) : void {
        // expand canvas
        $imageObj->setSize(65, 75);
        $gdLarger = $imageObj->getMergedGD();
        
        $imageSize = $imageObj->getSize();
        $this->assertEquals(65,$imageSize["w"]);
        $this->assertEquals(75,$imageSize["h"]);

        $this->assertEquals(65, imagesx($gdLarger));
        $this->assertEquals(75, imagesy($gdLarger));

        // reduce canvas (crop)
        $imageObj->setSize(20, 15);
        $gdLarger = $imageObj->getMergedGD();
        
        $imageSize = $imageObj->getSize();
        $this->assertEquals(20,$imageSize["w"]);
        $this->assertEquals(15,$imageSize["h"]);

        $this->assertEquals(20, imagesx($gdLarger));
        $this->assertEquals(15, imagesy($gdLarger));
    }
    #[Depends('testCreateFromFile')]
    public function testImageSetSizeInvalid(Image $imageObj) : void {
        // invalid params
        $exceptionClass="";
        try {
            $imageObj->setSize(20, 0);
        } catch(\Exception $e){
            $exceptionClass = get_class($e);
        }
        $this->assertEquals($exceptionClass, \InvalidArgumentException::class);
        
        $exceptionClass="";
        try {
            $imageObj->setSize(0, 20);
        } catch(\Exception $e){
            $exceptionClass = get_class($e);
        }
        $this->assertEquals($exceptionClass, \InvalidArgumentException::class);

        $exceptionClass="";
        try {
            $imageObj->setSize(-1, 20);
        } catch(\Exception $e){
            $exceptionClass = get_class($e);
        }
        $this->assertEquals($exceptionClass, \InvalidArgumentException::class);

        $exceptionClass="";
        try {
            $imageObj->setSize(20, -1);
        } catch(\Exception $e){
            $exceptionClass = get_class($e);
        }
        $this->assertEquals($exceptionClass, \InvalidArgumentException::class);
    }
    

    #[Depends('testCreateFromGD')]
    public function testNewLayer(Image $imageObj) : Layer  {
        $layer = $imageObj->newLayer("TestLayer");
        $this->assertInstanceOf(Layer::class, $layer);

        //layerstack
        $layerFromStack = $imageObj->getLayerByIndex(1);
        $this->assertEquals($layerFromStack, $layer);


        //dimensions
        $imageSize = $imageObj->getSize();
        $layerSize = $layer->getDimensions();

        $this->assertEquals($layerSize['w'], $imageSize['w']);
        $this->assertEquals($layerSize['h'], $imageSize['h']);

        return $layer;
    }

    private function createTestImageObj() {
        $imageObj = Image::createFromFile(__DIR__ . "/BasicSquares.png");
        return $imageObj;
    }

}