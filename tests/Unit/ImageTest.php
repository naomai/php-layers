<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Naomai\PHPLayers\Image;
use Naomai\PHPLayers\Layer;
use Naomai\PHPLayers\Test\Assert as ImageAssert;

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

        ImageAssert::assertCallableThrows(
            \RuntimeException::class, 
            function() {
                Image::createFromFile(__DIR__ . "/../TestImages/NonExistingImage.png");
            }
        );

        ImageAssert::assertCallableThrows(
            \RuntimeException::class, 
            function() {
                Image::createFromFile(__DIR__ . "/../TestImages/GarbageBytes.dat");
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

        $newLayer
            ->expects($this->once())
            ->method('getParentImg')
            ->will($this->returnValue($testImg));

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
        $newLayer
            ->expects($this->once())
            ->method('getParentImg')
            ->will($this->returnValue($testImg));

        
        $testImg->layerPutBottom($newLayer);
        $layerAtBottom = $testImg->getLayerByIndex(0);
        $this->assertSame($newLayer, $layerAtBottom);
    }

    public function testNewLayer() : void {
        $testImg = static::createEmptyImage();

        $layersCountBefore = $testImg->getLayerCount();
        $layer = $testImg->newLayer();
        $layersCountAfter = $testImg->getLayerCount();

        $this->assertSame($layersCountBefore+1, $layersCountAfter);
        
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

    public function testGetMerged(){
        $testImg = static::createTwoLayerImage();
        $mergedLayer = $testImg->getMerged();

        $this->assertTrue($mergedLayer instanceof \Naomai\PHPLayers\Layer);

        //The original layer set is left intact.
        $layerCount = $testImg->getLayerCount();
        $this->assertEquals(2, $layerCount);

        //The new layer is not attached to the image
        $mergedLayerIndexInStack = $testImg->getLayerStack()->getIndexOf($mergedLayer);
        $this->assertFalse($mergedLayerIndexInStack);

        //image without layers should also produce a result
        $nullImg = new Image(50, 80, false);
        $mergedLayer = $nullImg->getMerged();
        $this->assertTrue($mergedLayer instanceof \Naomai\PHPLayers\Layer);

    }

    public function testGetMergedGD(){
        $testImg = static::createTwoLayerImage();
        $mergedGD = $testImg->getMergedGD();

        $this->assertTrue($mergedGD instanceof \GdImage);

        //The original layer set is left intact.
        $layerCount = $testImg->getLayerCount();
        $this->assertEquals(2, $layerCount);

        //image without layers should also produce a result
        $nullImg = new Image(50, 80, false);
        $mergedGD = $nullImg->getMergedGD();
        $this->assertTrue($mergedGD instanceof \GdImage);

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

    public function testExport(){
        $testImg = static::createEmptyImage();
        $exporter = $testImg->export();
        $this->assertTrue(
            $exporter instanceof \Naomai\PHPLayers\Helpers\ImageExporter
        );
    }

    /* TODO delegate to ImageExporterTest */
    public function testGetDataUrlPNG() {
        $testImg = static::createEmptyImage();
        $dataUrl = $testImg->getDataUrlPNG();

        $this->assertStringStartsWith("data:", $dataUrl);
        $this->assertStringContainsString("image/png", $dataUrl);

        $imageData = file_get_contents($dataUrl);
        ImageAssert::assertValidImageData($imageData);
    }

    public function testGetDataUrlJPEG() {
        $testImg = static::createEmptyImage();
        $dataUrl = $testImg->getDataUrlJPEG();

        $this->assertStringStartsWith("data:", $dataUrl);
        $this->assertStringContainsString("image/jpeg", $dataUrl);

        $imageData = file_get_contents($dataUrl);
        ImageAssert::assertValidImageData($imageData);
    }


    public function testConstructWithInvalidDimensions() : void {
        ImageAssert::assertCallableThrows(
            \InvalidArgumentException::class, function() {
                new Image(1, 0);
            } 
        );
        ImageAssert::assertCallableThrows(
            \InvalidArgumentException::class, function() {
                new Image(0, 1);
            } 
        );

        ImageAssert::assertCallableThrows(
            \InvalidArgumentException::class, function() {
                new Image(1, -1);
            } 
        );
        ImageAssert::assertCallableThrows(
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


}