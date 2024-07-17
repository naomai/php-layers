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

        $this->assertCallableThrows(
            \RuntimeException::class, 
            function() {
                Image::createFromFile(__DIR__ . "/../TestImages/NonExistingImage.png");
            }
        );
    }

    public function testGetLayerCount() : void {
        $zeroLayerImage = new Image(50, 50, false);
        $oneLayerImage = static::createEmptyImage();
        $twoLayerImage = static::createTwoLayerImage();

        $this->assertSame(0, $zeroLayerImage->getLayerCount());
        $this->assertSame(1, $oneLayerImage->getLayerCount());
        $this->assertSame(2, $twoLayerImage->getLayerCount());
    }

    public function testLayerPutTop() {
        $testImg = static::createEmptyImage();

        $newLayer = $this->createMock(Layer::class);
        $newLayer->expects($this->once())
            ->method('setParentImg')
            ->with($testImg);

        $testImg->layerPutTop($newLayer);
        $layerAtTop = $testImg->getLayerByIndex(-1);
        $this->assertSame($newLayer, $layerAtTop);
    }

    public function testLayerPutBottom() {
        $testImg = static::createEmptyImage();

        $newLayer = $this->createMock(Layer::class);
        $newLayer->expects($this->once())
            ->method('setParentImg')
            ->with($testImg);

        $testImg->layerPutBottom($newLayer);
        $layerAtBottom = $testImg->getLayerByIndex(0);
        $this->assertSame($newLayer, $layerAtBottom);
    }

    public function testNewLayer() : void {
        $testImg = static::createEmptyImage();

        $layersCountBefore = $testImg->getLayerCount();
        $layer = $testImg->newLayer();
        $layersCountAfter = $testImg->getLayerCount();

        $this->assertSame(1, $layersCountBefore);
        $this->assertSame(2, $layersCountAfter);
        
        $layerStack = $testImg->getLayerStack();
        $newLayerIndex = $layerStack->getIndexOf($layer);
        $this->assertSame(1, $newLayerIndex);

        $layerWithName = $testImg->newLayer("grape");
        $this->assertSame("grape", $layerWithName->name);
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

    public function testReorder() {
        $testImg = static::createTwoLayerImage();
        $bg = $testImg->getLayerByIndex(0);
        $fg = $testImg->getLayerByIndex(1);

        $reorderCall = $testImg->reorder($bg);
        // type check
        $this->assertTrue($reorderCall instanceof \Naomai\PHPLayers\Helpers\LayerReorderCall);

        // reorder call has layerstack access
        $layerStack = $reorderCall->getLayerStack();
        $bgPosition = $layerStack->getIndexOf($bg);
        $this->assertSame(0, $bgPosition);
        $fgPosition = $layerStack->getIndexOf($fg);
        $this->assertSame(1, $fgPosition);
    }

    public function testGetDataUrlPNG() {
        $testImg = static::createEmptyImage();
        $dataUrl = $testImg->getDataUrlPNG();

        $this->assertStringStartsWith("data:", $dataUrl);
        $this->assertStringContainsString("image/png", $dataUrl);

        $imageData = file_get_contents($dataUrl);
        $this->assertValidImageData($imageData);
    }

    public function testGetDataUrlJPEG() {
        $testImg = static::createEmptyImage();
        $dataUrl = $testImg->getDataUrlJPEG();

        $this->assertStringStartsWith("data:", $dataUrl);
        $this->assertStringContainsString("image/jpeg", $dataUrl);

        $imageData = file_get_contents($dataUrl);
        $this->assertValidImageData($imageData);
    }

    public function testGetComposer() {
        $testImg = static::createEmptyImage();
        $composer = $testImg->getComposer();
        $this->assertTrue(
            $composer instanceof \Naomai\PHPLayers\Composers\LayerComposerBase
        );
    }

    public function testSetComposer() {
        $testImg = static::createEmptyImage();

        $composerMock = $this->createMock(
            \Naomai\PHPLayers\Composers\DefaultComposer::class
        );
        $composerMock->expects($this->once())
            ->method('setImage')
            ->with($testImg);

        $testImg->setComposer($composerMock);
        $composer = $testImg->getComposer();
        $this->assertSame($composerMock, $composer);
    }


    public function testConstructWithInvalidDimensions() : void {
        $this->assertCallableThrows(
            \InvalidArgumentException::class, function() {
                new Image(1, 0);
            } 
        );
        $this->assertCallableThrows(
            \InvalidArgumentException::class, function() {
                new Image(0, 1);
            } 
        );

        $this->assertCallableThrows(
            \InvalidArgumentException::class, function() {
                new Image(1, -1);
            } 
        );
        $this->assertCallableThrows(
            \InvalidArgumentException::class, function() {
                new Image(-1, 1);
            } 
        );
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

    private static function assertCallableThrows(string $className, callable $function){
        $exceptionClass="";
        try {
            call_user_func($function);
        } catch(\Exception $e){
            $exceptionClass = get_class($e);
        }
        static::assertEquals(
            $exceptionClass, $className, 
            "Failed asserting that call throws {$className}"
        );

    }

    private static function assertValidImageData(string $imgBinary){
        self::assertTrue(
            imagecreatefromstring($imgBinary)!==false,
            "Failed asserting that string is a valid image data"
        );
    }

}