<?php
namespace Naomai\PHPLayers\Filters;
use Naomai\PHPLayers as GDW;

abstract class FilterBase {
    protected $destLayer;
    protected $destGD;
    
    public function __construct($layerObj){
        $this->attachToLayer($layerObj);
    }
    
    public function attachToLayer($layerObj){
        $this->destLayer = $layerObj;
        $this->destGD = $layerObj->getGDHandle();
    }
}