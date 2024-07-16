<?php
namespace Naomai\PHPLayers\Composers;

use Naomai\PHPLayers;
use Naomai\PHPLayers\Layer;

class DefaultComposer extends LayerComposerBase {
    public bool $gammaBlending = false;

    public function mergeAll() : Layer {
        $layers = $this->layers->getAll();
        foreach($layers as $layer){
            $this->preprocessLayer($layer);
        }

        if(count($layers)==1){
            /* special case - image with one layer, no need for merging
               just make sure the resulting layer is of the same dimensions 
               as image */
            $layerSingle = reset($layers);

            $layerSize = $layerSingle->getDimensions();
            $imageSize = $this->image->getSize();

            if(
                $layerSize['w'] != $imageSize['w'] 
                || $layerSize['h'] != $imageSize['h'] 
            ) {
                $layerSingle->transformPermanently();
            }
            imagesavealpha($layerSingle->getGDHandle(), true);
            return $layerSingle;
        }
        
        while(count($layers) > 1){
            $layerBottom = array_shift($layers);
            $layerTop = array_shift($layers);
            $newLayer = $this->mergeDown($layerTop, $layerBottom);
            array_unshift($layers, $newLayer);
        };
        $layerResult = reset($layers);
        imagesavealpha($layerResult->getGDHandle(), true);

        return $layerResult;
    }

    protected function mergeDown(Layer $layerTop, Layer $layerBottom) : Layer {
        $gdTop = $layerTop->getGDHandle();
        $gdBottom = $layerBottom->getGDHandle();
        
        $topDimensions = $layerTop->getDimensions();
        $bottomDimensions = $layerBottom->getDimensions();
        $imgSize = $this->image->getSize();
        
        $newLayerGD = imagecreatetruecolor($imgSize['w'], $imgSize['h']);
        imagealphablending($newLayerGD, false);
        imagefill($newLayerGD, 0, 0, 0x7f000000);
        imagealphablending($newLayerGD, true);
        imagecopy(
            $newLayerGD, $gdBottom, 
            0, 0, 
            0, 0, 
            $bottomDimensions['w'], $bottomDimensions['h']
        ); 
            
         
        self::mergeWithOpacity(
            $newLayerGD, $gdTop,
            0, 0,
            0, 0,
            $topDimensions['w'], $topDimensions['h'],
            $layerTop->getOpacity(),
            $this->gammaBlending
        );
            
        imagesavealpha($newLayerGD, true);
        $newLayer = new Layer();
        $newLayer->setParentImg($this->image);
        $newLayer->importFromGD($newLayerGD);
        return $newLayer;
    }
    
    // imagecopymerge equivalent with correct handling of alpha channel
    // $opacityPct: 0-transparent, 100-opaque
    static function mergeWithOpacity(
        \GdImage $dst_im, \GdImage $src_im, 
        int $dst_x, int $dst_y, 
        int $src_x, int $src_y, 
        int $src_width, int $src_height, 
        int $opacityPct,
        bool $gammaBlending=false
    ) {
        $opacityPct=PHPLayers\clamp_int($opacityPct, 0, 100);

        if($opacityPct==0) {
            // fully transparent, skip
            return;
        }

        $dstImgW = imagesx($dst_im);
        $dstImgH = imagesy($dst_im);
        
        imagealphablending($dst_im, true);

        $imageToCopy = $src_im;
        
        if($opacityPct!=100) {

            if($gammaBlending) {
                self::imageMergeGammaBlend($dst_im, $src_im, $dst_x, $dst_y, $src_width, $src_height, $opacityPct);
                $imageToCopy = null;
            }else{
                $imageToCopy = self::intermediateForMerge($src_im, $dst_x, $dst_y, $src_width, $src_height, $opacityPct);
            }
            
        }

        if($imageToCopy !==null) {
            imagecopy(
                $dst_im, $imageToCopy,
                $dst_x, $dst_y,
                $src_x, $src_y,
                $src_width, $src_height
            );
        }
    }
    
    /**
     * Create new GdImage, and put a fragment of source while applying opacity
     *
     * @return void
     */
    protected static function intermediateForMerge(
        \GdImage $src_im, 
        int $dst_x, int $dst_y, 
        int $src_width, int $src_height, 
        int $opacityPct
    ) : \GdImage {
        $opFrac = $opacityPct / 100;

        $imageIntermediate = imagecreatetruecolor($src_width, $src_height);
        imagealphablending($imageIntermediate, false);
        imagefill($imageIntermediate, 0, 0, 0x7F000000);
            
        for($y = 0; $y<$src_height; $y++) {
            for($x = 0; $x<$src_width; $x++) {
                $srcPixX = $x; 
                $srcPixY = $y;
                $dstPixX = $dst_x + $x; 
                $dstPixY = $dst_y + $y;
                $pixSrc = imagecolorat($src_im, $srcPixX, $srcPixY);
                
                $opacitySrc = (($pixSrc>>24)&0x7F);
                $opacityAdjusted = (int)(127-(127-$opacitySrc) * $opFrac);
                
                imagesetpixel(
                    $imageIntermediate, 
                    $dstPixX, $dstPixY, 
                    ($pixSrc & 0xFFFFFF) | ($opacityAdjusted << 24)
                );
            }
        }
        return $imageIntermediate;
    }

    protected static function imageMergeGammaBlend(
        $bottom_im, $top_im,
        $dst_x, $dst_y, 
        $src_width, $src_height, 
        $opacityPct
    ) : void {
        $opFrac = $opacityPct / 100;

        imagealphablending($bottom_im, false);
            
        for($y = 0; $y<$src_height; $y++) {
            for($x = 0; $x<$src_width; $x++) {
                $srcPixX = $x; 
                $srcPixY = $y;
                $dstPixX = $dst_x + $x; 
                $dstPixY = $dst_y + $y;
                $pixTop = imagecolorat($top_im, $srcPixX, $srcPixY);
                $pixBottom = imagecolorat($bottom_im, $dstPixX, $dstPixY);
                
                $colorBlended = self::blendColorsWithGamma($pixBottom, $pixTop, $opFrac);
                
                
                imagesetpixel(
                    $bottom_im, 
                    $dstPixX, $dstPixY, 
                    $colorBlended
                );
            }
        }
    }
    
    protected static function blendColorsWithGamma(int $color1, int $color2, float $blend) : int {
        if($blend==0 || ($color2 & 0x7F000000) == 0x7F000000) {
            return $color1;
        }
        $color1Linear = self::convertSRGBColorToLinearArray($color1);
        $color2Linear = self::convertSRGBColorToLinearArray($color2);

        $opacity1 = (127 - $color1Linear[3])/127;
        $opacity2 = (127 - $color2Linear[3])/127 * $blend;

        $opacity3 = $opacity1 * (1-$opacity2);
        $opacity3 += $opacity2;

        $alphaBlend1 = $opacity1 * (1-$opacity2) / $opacity3;
        $alphaBlend2 = $opacity2 / $opacity3;

        $color3Linear = [
            $color1Linear[0] * $alphaBlend1 + $color2Linear[0] * $alphaBlend2,
            $color1Linear[1] * $alphaBlend1 + $color2Linear[1] * $alphaBlend2,
            $color1Linear[2] * $alphaBlend1 + $color2Linear[2] * $alphaBlend2,
            127-round($opacity3 * 127)
        ];

        $colorResult = self::convertLinearColorArrayToSRGB($color3Linear);

        return $colorResult;
    }

    /* implementation based on VrExtensionsJni.cpp from Android Open Source Project */

    private static function convertSRGBChannelToLinear(int $cs) : float {
        static $linearLUT = null;
        if($linearLUT===null) {
            $linearLUT = self::generateConversionLutLinear();
        }
        return $linearLUT[$cs];
        
    }
    private static function convertLinearChannelToSRGB(float $cs) : int {
        static $srgbLUT = null;
        if($srgbLUT===null) { 
            $srgbLUT = self::generateConversionLutSrgb();
        }
        $idx = min(round($cs*10000), 10000);
        return $srgbLUT[$idx];
    }
    private static function convertSRGBColorToLinearArray(int $color) : array {
        $r = self::convertSRGBChannelToLinear($color & 0xff);
        $g = self::convertSRGBChannelToLinear(($color >> 8) & 0xff);
        $b = self::convertSRGBChannelToLinear(($color >> 16) & 0xff);
        $a = (($color >> 24) & 0x7f);
        return [$r, $g, $b, $a];
    }
    private static function convertLinearColorArrayToSRGB(array $colorArr) : int {
        $r = self::convertLinearChannelToSRGB($colorArr[0]);
        $g = self::convertLinearChannelToSRGB($colorArr[1]);
        $b = self::convertLinearChannelToSRGB($colorArr[2]);
        $a = $colorArr[3];
        return ($a << 24) | ($b << 16) | ($g << 8) | $r;
    }

    /* lookup tables for gamma correction */

    private static function generateConversionLutLinear() : \ArrayAccess {
        $lutLinear = new \SplFixedArray(256);
        for(
            $c=0, $cs=0; 
            $c<256; 
            $c++, $cs=$c/255
        ) {
            if ($cs <= 0.04045) {
                $lutLinear[$c] = $cs / 12.92;
            } else {
                $lutLinear[$c] = pow(($cs + 0.055) / 1.055, 2.4);
            }
        }
        return $lutLinear;
    }

    private static function generateConversionLutSrgb() : \ArrayAccess {
        $lutSrgb = new \SplFixedArray(10001);
        for(
            $c=0, $cs=0; 
            $cs<=1; 
            $c++, $cs+=0.0001
        ) {
            $valueFloat = null;
            if($cs <= 0.0) {
                $valueFloat = 0.0;
            } elseif ($cs < 0.0031308) {
                $valueFloat = 12.92 * $cs;
            } elseif ($cs < 1.0) {
                $valueFloat = 1.055 * pow($cs, 0.41666) - 0.055;
            } else {
                $valueFloat = 1.0;
            }
            $lutSrgb[$c] = round($valueFloat * 255);
        }
        return $lutSrgb;
    }


}