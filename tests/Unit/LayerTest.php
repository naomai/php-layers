<?php declare(strict_types=1);

use Naomai\PHPLayers\Helpers\LayerReorderCall;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Naomai\PHPLayers;
use Naomai\PHPLayers\Image;
use Naomai\PHPLayers\Layer;
use PHPUnit\Framework\MockObject\MockObject;

require_once 'src/Image.php';

/**
 * @covers Naomai\PHPLayers\Layer
 */
final class LayerTest extends TestCase {

    public function testReorder() {
        $testLayer = $this->createAttachedLayer();

        $reorderCall = $testLayer->reorder();
        // type check
        $this->assertTrue($reorderCall instanceof \Naomai\PHPLayers\Helpers\LayerReorderCall);

        // reorder call has layerstack access
        $layerStack = $reorderCall->getLayerStack();
        $layerPosition = $layerStack->getIndexOf($testLayer);
        $this->assertSame(0, $layerPosition);

        $this->expectException(\RuntimeException::class);
        $testLayerDetached = $this->createEmptyLayer();
        $testLayerDetached->reorder();
    }


    public function testGetGdHandle() {
        $testLayer = $this->createAttachedLayer();

        $gd = $testLayer->getGDHandle();

        $this->assertSame(\GdImage::class, get_class($gd));
        $this->assertSame(80, imagesx($gd));
        $this->assertSame(50, imagesy($gd));        
    }

    public function testSelect() {
        $testLayer = $this->createEmptyLayer();

        $selection = $testLayer->select(20, 30, 10, 15);
        $this->assertSame(PHPLayers\Selection::class, get_class($selection));

        $selectionRect = $selection->getCurrentRect();
        $this->assertEquals(
            ['x'=>20,'y'=>30,'w'=>10,'h'=>15],
            $selectionRect
        );
    }

    public function testSelectSurface() {
        $testLayer = $this->createEmptyLayer();
        $testLayer->setSurfaceDimensions(15, 10, 20, 33);

        $selection = $testLayer->selectSurface();
        $this->assertSame(PHPLayers\Selection::class, get_class($selection));

        $selectionRect = $selection->getCurrentRect();
        $this->assertEquals(
            ['x'=>20,'y'=>33,'w'=>15,'h'=>10],
            $selectionRect
        );
    }

    public function testSelectWhole() {
        $testLayer = $this->createAttachedLayer();
        $testLayer->setSurfaceDimensions(15, 10, 20, 33);

        $selection = $testLayer->selectWhole();
        $this->assertSame(PHPLayers\Selection::class, get_class($selection));

        $selectionRect = $selection->getCurrentRect();
        $this->assertEquals(
            ['x'=>0,'y'=>0,'w'=>80,'h'=>50],
            $selectionRect
        );
    }

    private function createTestImage() : Image {
        return new Image(80, 50, false);
    }

    private function createImageMock() : Image {
        $imgStub = $this->createMock(Image::class);
        $reorderStub = $this->createStub(LayerReorderCall::class);

        $imgStub->method("reorder")->willReturn($reorderStub);
        $imgStub->method("getSize")->willReturn(
            ['w'=>80, 'h'=>50]
        );
        return $imgStub;
    }

    private function createEmptyLayer() : Layer {
        $testLayer = new Layer();
        return $testLayer;
    }

    private function createAttachedLayer() : Layer {
        $testLayer = new Layer();
        $testImg = $this->createTestImage();
        $testImg->layerPutTop($testLayer);
        return $testLayer;
    }

    private function assertCallableThrows(string $className, callable $function){
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

}