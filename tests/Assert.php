<?php

namespace Naomai\PHPLayers\Test;

use \PHPUnit\Framework\Assert as PHPUnitAssert;

final class Assert {
    
    public static function assertCallableThrows(string $className, callable $function){
        $exceptionClass="";
        try {
            call_user_func($function);
        } catch(\Exception $e){
            $exceptionClass = get_class($e);
        }
        PHPUnitAssert::assertEquals(
            $exceptionClass, $className, 
            "Failed asserting that call throws {$className}"
        );

    }

    public static function assertValidImageData(string $imgBinary){
        PHPUnitAssert::assertTrue(
            imagecreatefromstring($imgBinary)!==false,
            "Failed asserting that string is a valid image data"
        );
    }

   
    public static function assertSameColorAt(\GdImage $img, int $colorExpected, int $x, int $y, int $tolerance = 2) {
        $colorActual = imagecolorat($img, $x, $y);
        $colorDiff = static::compareColors($colorExpected, $colorActual);
        PHPUnitAssert::assertTrue(
            $colorDiff <= $tolerance,
            sprintf(
                "Failed asserting that color #%06x is close to #%06x within tolerance %d", 
                $colorActual, $colorExpected, $tolerance
            )
        );
    }

    public static function assertDifferentColorAt(\GdImage $img, int $colorExpected, int $x, int $y, int $tolerance = 2) {
        $colorActual = imagecolorat($img, $x, $y);
        $colorDiff = static::compareColors($colorExpected, $colorActual);
        PHPUnitAssert::assertFalse(
            $colorDiff <= $tolerance,
            sprintf(
                "Failed asserting that color #%06x is different to #%06x within tolerance %d", 
                $colorActual, $colorExpected, $tolerance
            )
        );
    }

    public static function assertSameAlphaAt(\GdImage $img, int $colorExpected, int $x, int $y){
        $colorActual = imagecolorat($img, $x, $y);

        if($colorExpected & 0x7F == 0x7F) {
            $colorExpected &= 0x7F;
            $colorActual &= 0x7F;
        }
        PHPUnitAssert::assertEquals($colorExpected, $colorActual);
    }

    private static function compareColors(int $color1, int $color2) : int {
        $aDiff = abs((($color1 & 0x7F000000) - ($color2 & 0x7F000000))>>24);
        $rDiff = abs((($color1 & 0xFF0000) - ($color2 & 0xFF0000))>>16);
        $gDiff = abs((($color1 & 0xFF00) - ($color2 & 0xFF00))>>8);
        $bDiff = abs((($color1 & 0xFF) - ($color2 & 0xFF)));
        
        $maxDiff = max($aDiff, $rDiff, $gDiff, $bDiff);
        return $maxDiff;
    }
}