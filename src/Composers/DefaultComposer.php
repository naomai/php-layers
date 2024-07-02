<?php

namespace Naomai\PHPLayers\Composers;
use Naomai\PHPLayers as GDW;

class DefaultComposer{
    protected GDW\LayerStack $layers;
    protected $image;
    
    public function __construct(){

    }
    
    public function fillLayers(GDW\LayerStack $layers){
        $this->layers = $layers;
    }
    
    public function preprocessLayer($layerObj){
        if($layerObj->renderer != null && $layerObj->renderer instanceof GDW\Renderers\ILayerRenderer){
            $layerObj->renderer->apply();
        }
    }

    public function setImage($image){
        $this->image = $image;
    }

    public function mergeAll(){
        $layers = $this->layers->getAll();
        foreach($layers as $layer){
            $this->preprocessLayer($layer);
        }
        $imgSize = $this->image->getSize();
        $bgLayer = new GDW\Layer($imgSize['w'],$imgSize['h'],0,0);
        $bgLayer->clear();

        array_unshift($layers,$bgLayer);
        
        while(count($layers) > 1){
            $layerBottom = array_shift($layers);
            $layerTop = array_shift($layers);
            $newLayer = $this->mergeDown($layerTop,$layerBottom);
            array_unshift($layers,$newLayer);
        };
    
        return reset($layers);
    }

    public function mergeDown($layerTop, $layerBottom){
        $gdTop = $layerTop->getGDHandle();
        $gdBottom = $layerBottom->getGDHandle();
        
        $topDimensions = $layerTop->getLayerDimensions();
        $bottomDimensions = $layerBottom->getLayerDimensions();
        $imgSize = $this->image->getSize();
        
        $newLayerGD = imagecreatetruecolor($imgSize['w'],$imgSize['h']);
        imagefill($newLayerGD,0,0,0x7f000000);
        
        imagecopy($newLayerGD, $gdBottom, 
            $bottomDimensions['x'],$bottomDimensions['y'], 
            0, 0, 
            $bottomDimensions['w'],$bottomDimensions['h']); 
            
            
        self::mergeWithOpacity($newLayerGD,$gdTop,
            $topDimensions['x'],$topDimensions['y'],
            0,0,
            $topDimensions['w'],$topDimensions['h'],
            $layerTop->getOpacity()
            );
            
        imagesavealpha($newLayerGD,true);
        $newLayer = new GDW\Layer($newLayerGD);
        return $newLayer;
    }
    
    // like imagecopy, but with opacity control
    // $op: 0-transparent, 100-opaque
    static function mergeWithOpacity($dst_im,$src_im,$dst_x,$dst_y,$src_x,$src_y,$src_w,$src_h,$op){
        $op=GDW\clamp_int($op,0,100);
        $dstImgW = imagesx($dst_im);
        $dstImgH = imagesy($dst_im);
        
        imagealphablending ($dst_im,true);
        if($op==100){
            imagecopy($dst_im,$src_im,$dst_x,$dst_y,$src_x,$src_y,$src_w,$src_h); // native equivalent
        }else if($op==0){
        
        }else{
            $opFracP = $op / 100;
            $opFracN = 1 - $opFracP;
            
            $startX = $dst_x < 0 ? -$dst_x : 0;
            $startY = $dst_y < 0 ? -$dst_y : 0;
            $endX = $dst_x + $src_w > $dstImgW ? $dstImgW - $dst_x : $src_w;
            $endY = $dst_y + $src_h > $dstImgH ? $dstImgH - $dst_y : $src_h;
            
            for($y = $startY; $y<$endY; $y++){
                for($x = $startX; $x<$endX; $x++){
                    $srcPixX = $src_x + $x; $srcPixY = $src_y + $y;
                    $dstPixX = $dst_x + $x; $dstPixY = $dst_y + $y;
                    $pixSrc = imagecolorat($src_im,$srcPixX,$srcPixY);
                    $pixDst = imagecolorat($dst_im,$dstPixX,$dstPixY);
                    
                    $srcO = (($pixSrc>>24)&0x7F);
                    $srcO = (int)(127-(127-$srcO) * $opFracP);
                    
                    imagesetpixel($dst_im,$dstPixX,$dstPixY,($pixSrc & 0xFFFFFF) | ($srcO << 24));
                }
            }
        }
    }
}