<?php
namespace Naomai\PHPLayers\Composers;

use Naomai\PHPLayers;
use Naomai\PHPLayers\Layer;

class DefaultComposer extends LayerComposerBase {


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
            $layerTop->getOpacity()
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
                $imageToCopy = self::intermediateForMergeGammaBlend($src_im, $dst_im, $dst_x, $dst_y, $src_width, $src_height, $opacityPct);
            }else{
                $imageToCopy = self::intermediateForMerge($src_im, $dst_x, $dst_y, $src_width, $src_height, $opacityPct);
            }
            
        }

        imagecopy(
            $dst_im, $imageToCopy,
            $dst_x, $dst_y,
            $src_x, $src_y,
            $src_width, $src_height
        );
    }

    protected static function intermediateForMerge(
        $src_im, 
        $dst_x, $dst_y, 
        $src_width, $src_height, 
        $opacityPct
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

    protected static function intermediateForMergeGammaBlend(
        $src_im, $dst_im,
        $dst_x, $dst_y, 
        $src_width, $src_height, 
        $opacityPct
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
                $pixDst = imagecolorat($src_im, $dstPixX, $dstPixY);
                
                $opacitySrc = 127-(($pixSrc>>24)&0x7F);
                $opacityDst = 127-(($pixDst>>24)&0x7F);

                if($opacitySrc == 0 || $opacityDst == 0) {
                    continue;
                }

                $opacityAdjusted = (int)(127 - ($opacitySrc * $opFrac));

                imagesetpixel(
                    $imageIntermediate, 
                    $dstPixX, $dstPixY, 
                    ($pixSrc & 0xFFFFFF) | ($opacityAdjusted << 24)
                );
            }
        }
        return $imageIntermediate;
    }
    
    protected static function blendColorsWithGamma(int $color1, int $color2, float $blend){
        $color1Linear = self::convertSRGBColorToLinearArray($color1);
        $color2Linear = self::convertSRGBColorToLinearArray($color2);
        //TODO
    }

    /* implementation based on VrExtensionsJni.cpp from Android Open Source Project */

    private static function convertSRGBChannelToLinear(float $cs) {
        if ($cs <= 0.04045) {
            return $cs / 12.92;
        } else{
            return pow(($cs + 0.055) / 1.055, 2.4);
        }
    }
    private static function convertLinearChannelToSRGB(float $cs) {
        if($cs <= 0.0) {
            return 0.0;
        } elseif ($cs < 0.0031308) {
            return 12.92 * $cs;
        } elseif ($cs < 1.0) {
            return 1.055 * pow($cs, 0.41666) - 0.055;
        } else {
            return 1.0;
        }
    }
    private static function convertSRGBColorToLinearArray(int $color) {
        $r = self::convertSRGBChannelToLinear(($color & 0xff) / 255.0);
        $g = self::convertSRGBChannelToLinear((($color >> 8) & 0xff) / 255.0);
        $b = self::convertSRGBChannelToLinear((($color >> 16) & 0xff) / 255.0);
        return [$r, $g, $b, $color>>24];
    }
    private static function convertLinearColorArrayToSRGB(array $colorArr) {
        $r = self::convertLinearChannelToSRGB($colorArr[0]);
        $g = self::convertLinearChannelToSRGB($colorArr[1]);
        $b = self::convertLinearChannelToSRGB($colorArr[2]);
        $r8 = round($r * 255.0);
        $g8 = round($g * 255.0);
        $b8 = round($b * 255.0);
        $a8 = $colorArr[3];
        return ($a8 << 24) | ($b8 << 16) | ($g8 << 8) | $r8;
    }
}