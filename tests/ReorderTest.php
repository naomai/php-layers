<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Naomai\PHPLayers;

final class ReorderTest extends TestCase {
    public function testLayerCreationOrder() : void {
        [$imageObj, $layers] = $this->createTestImageObj();
        $this->assertEquals(3, count($layers));
        
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test0", "_Test1", "_Test2"], $names);
    }

    public function testLayerReorderTopBottom() : void {
        [$imageObj, $layers] = $this->createTestImageObj();

        $layers[0]->reorder()->putTop();
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test1", "_Test2", "_Test0"], $names);

        $layers[2]->reorder()->putBottom();
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test2", "_Test1", "_Test0"], $names);

        $imageObj->reorder($layers[1])->putTop();
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test2", "_Test0", "_Test1"], $names);

        $imageObj->reorder($layers[0])->putBottom();
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test0", "_Test2", "_Test1"], $names);
    }

    public function testLayerReorderRelative() : void {
        [$imageObj, $layers] = $this->createTestImageObj();

        $layers[0]->reorder()->putOver($layers[2]);
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test1", "_Test2", "_Test0"], $names);

        $layers[0]->reorder()->putBehind($layers[2]);
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test1", "_Test0", "_Test2"], $names);

        $imageObj->reorder($layers[1])->putOver($layers[2]);
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test0", "_Test2", "_Test1"], $names);

        $imageObj->reorder($layers[2])->putBehind($layers[0]);
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test2", "_Test0", "_Test1"], $names);
    }


    public function testLayerReorderAbsolute() : void {
        [$imageObj, $layers] = $this->createTestImageObj();

        $layers[0]->reorder()->putAt(2);
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test1", "_Test2", "_Test0"], $names);

        $layers[1]->reorder()->putAt(1);
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test2", "_Test1", "_Test0"], $names);

        $layers[2]->reorder()->putAt(5);
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test1", "_Test0", "_Test2"], $names);

        $layers[1]->reorder()->putAt(-1);
        $names = self::extractNames($imageObj);
        $this->assertEquals(["_Test0", "_Test2", "_Test1"], $names);

        $this->expectException(\InvalidArgumentException::class);
        $layers[1]->reorder()->putAt(-4);

    }

    private function createTestImageObj() : array {
        $imageObj = PHPLayers\Image::createFromFile(__DIR__ . "/BasicSquares.png");
        $layers = [];
        $background = $imageObj->getLayerByIndex(0);
        $background->name = "_Test0";
        $layers[] = $background;
        $layers[] = $imageObj->newLayer("_Test1");
        $layers[] = $imageObj->newLayer("_Test2");

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