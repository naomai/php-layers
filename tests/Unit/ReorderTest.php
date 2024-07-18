<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Naomai\PHPLayers;


/**
 * @covers Naomai\PHPLayers\Helpers\LayerReorderCall
 */
final class ReorderTest extends TestCase {
    public function testSelfCreateTestImageObj() : void {
        [$imageObj, $layers] = $this->createTestImageObj();
        $this->assertEquals(3, count($layers));
        $this->assertLayerOrder($imageObj, ["0", "1", "2"]);
    }

    public function testPutTop() : void {
        [$imageObj, $layers] = $this->createTestImageObj();

        $index = $layers[0]->reorder()->putTop();
        $this->assertEquals(2, $index);
        $this->assertLayerOrder($imageObj, ["1", "2", "0"]);
    }

    public function testPutBottom() : void {
        [$imageObj, $layers] = $this->createTestImageObj();

        $index = $layers[2]->reorder()->putBottom();
        $this->assertEquals(0, $index);
        $this->assertLayerOrder($imageObj, ["2", "0", "1"]);
    }

    public function testPutOver() : void {
        [$imageObj, $layers] = $this->createTestImageObj();

        //upwards
        $index = $layers[0]->reorder()->putOver($layers[2]);
        $this->assertEquals(2, $index);
        $this->assertLayerOrder($imageObj, ["1", "2", "0"]);

        //downwards
        $index = $layers[0]->reorder()->putOver($layers[1]);
        $this->assertEquals(1, $index);
        $this->assertLayerOrder($imageObj, ["1", "0", "2"]);
        
        $layerDetached = new PHPLayers\Layer();
        $this->expectException(\InvalidArgumentException::class);
        $layers[0]->reorder()->putOver($layerDetached);
    }

    public function testPutBehind() : void {
        [$imageObj, $layers] = $this->createTestImageObj();

        //upwards
        $index = $layers[0]->reorder()->putBehind($layers[2]);
        $this->assertEquals(1, $index);
        $this->assertLayerOrder($imageObj, ["1", "0", "2"]);

        //downwards
        $index = $layers[2]->reorder()->putBehind($layers[1]);
        $this->assertEquals(0, $index);
        $this->assertLayerOrder($imageObj, ["2", "1", "0"]);

        $layerDetached = new PHPLayers\Layer();
        $this->expectException(\InvalidArgumentException::class);
        $layers[0]->reorder()->putBehind($layerDetached);
    }


    public function testPutAt() : void {
        [$imageObj, $layers] = $this->createTestImageObj();

        //upwards
        $index = $layers[0]->reorder()->putAt(2);
        $this->assertEquals(2, $index);
        $this->assertLayerOrder($imageObj, ["1", "2", "0"]);

        $index = $layers[1]->reorder()->putAt(1);
        $this->assertEquals(1, $index);
        $this->assertLayerOrder($imageObj, ["2", "1", "0"]);

        //downwards
        $index = $layers[0]->reorder()->putAt(0);
        $this->assertEquals(0, $index);
        $this->assertLayerOrder($imageObj, ["0", "2", "1"]);

        //index beyond stack size - should be placed as last
        $index = $layers[2]->reorder()->putAt(5);
        $this->assertEquals(2, $index);
        $this->assertLayerOrder($imageObj, ["0", "1", "2"]);

        //negative index - count from the end of stack
        $index = $layers[1]->reorder()->putAt(-1);
        $this->assertEquals(2, $index);
        $this->assertLayerOrder($imageObj, ["0", "2", "1"]);

        //negative index beyond stack size - exception
        $this->expectException(\InvalidArgumentException::class);
        $layers[1]->reorder()->putAt(-4);

    }


    static public function assertLayerOrder(PHPLayers\Image  $imageObj, array $layerOrder) {
        $names = static::extractNames($imageObj);

        static::assertSame($layerOrder, $names);

    }

    static public function assertLayerAt(PHPLayers\Layer $layer, int $expectedIndex, PHPLayers\Image $imageObj) {
        $layerAtIndex = $imageObj->getLayerByIndex($expectedIndex);
        static::assertSame($layer, $layerAtIndex);

    }

    private function createTestImageObj() : array {
        $imageObj = new PHPLayers\Image(10, 10, false);
        $layers = [];
        $layers[] = $imageObj->newLayer("0");
        $layers[] = $imageObj->newLayer("1");
        $layers[] = $imageObj->newLayer("2");

        return [$imageObj, $layers];
    }

    static function extractNames($imageObj) {
        $names = [];
        $layerIndex = 0;
        while(!is_null($layer = $imageObj->getLayerByIndex($layerIndex))) {
            $names[] = $layer->name;
            $layerIndex++;
        }

        return $names;
    }

}