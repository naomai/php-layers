<?php
namespace Naomai\PHPLayers\Filters;

use Naomai\PHPLayers\Layer;

abstract class FilterBase {
    protected $destLayer;
    protected $destGD;
    
    public function __construct(?Layer $layerObj=null) {
        if($layerObj!==null) {
            $this->attachToLayer($layerObj);
        }
    }
    
    public function attachToLayer(Layer $layerObj) {
        $this->destLayer = $layerObj;
        $this->destGD = $layerObj->getGDHandle();
    }

    public function attachToGD(\GdImage $gdResource) {
        $this->destGD = $gdResource;
    }
}