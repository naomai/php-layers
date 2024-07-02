<?php
namespace Naomai\PHPLayers\Composers;

use Naomai\PHPLayers as GDW;

class TiledComposer extends DefaultComposer{
    public function mergeAll() {
        $layers = $this->layers->getAll();
        foreach($layers as $layer){
            $this->preprocessLayer($layer);
        }
        $imgSize = $this->image->getSize();
        $bgLayer = new GDW\Layer($imgSize['w'], $imgSize['h'], 0, 0);
        $bgLayer->clear();
        $bgGD = $bgLayer->getGDHandle();
        //imagealphablending($bgGD,false);
        
        $layersCount = count($layers);
        
        $layersGridSize = ceil(sqrt($layersCount));
        $tileWidth = $imgSize['w'] / $layersGridSize;
        $tileHeight = $imgSize['h'] / $layersGridSize;
        
        $x=0; $y=0;
        
        foreach($layers as $layer){
            $layerGD = $layer->getGDHandle();
            $layerDim = $layer->getLayerDimensions();
            
            $layerGlobalX = round($x * $tileWidth + $layerDim['x'] / $layersGridSize);
            $layerGlobalY = round($y * $tileHeight + $layerDim['y'] / $layersGridSize);
            $layerGlobalW = round($layerDim['w'] / $layersGridSize);
            $layerGlobalH = round($layerDim['h'] / $layersGridSize);
            
            imagecopyresampled(
                $bgGD, $layerGD, 
                $layerGlobalX, $layerGlobalY, 0, 0,
                $layerGlobalW, $layerGlobalH, $layerDim['w'], $layerDim['h']
            );
            
            $layerTag = $layer->name;
            if($layer->getOpacity() != 100) {
                $layerTag .= " (".round($layer->getOpacity())."%)";
            }
            
            imagestring(
                $bgGD, 5, 
                $layerGlobalX+3, $layerGlobalY+$layerGlobalH - 16, 
                $layerTag, 0x000000
            );
            imagestring(
                $bgGD, 5,
                $layerGlobalX+2, $layerGlobalY+$layerGlobalH - 17,
                $layerTag, 0xFFFFFF
            );
            
            $x++;
            if($x >= $layersGridSize) {
                $x=0; $y++;
            }
        }
        
        for($i = 1; $i < $layersGridSize; $i++) {
            imageline(
                $bgGD, 
                round($i*$tileWidth), 0, 
                round($i*$tileWidth), $imgSize['h'], 
                0xFF0000
            );
            imageline(
                $bgGD, 
                0, $i*$tileHeight, 
                $imgSize['w'], $i*$tileHeight, 
                0xFF0000
            );
        }
        
        imagesavealpha($bgGD, true);
        return $bgLayer;
    }
}