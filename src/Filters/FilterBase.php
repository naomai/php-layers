<?php
namespace Naomai\PHPLayers\Filters;
use Naomai\PHPLayers as GDW;

abstract class FilterBase {
    protected $destLayer;
    protected $destGD;
    
    public function __construct($layerObj=null){
        if($layerObj!==null)
            $this->attachToLayer($layerObj);
    }
    
    public function attachToLayer($layerObj){
        $this->destLayer = $layerObj;
        $this->destGD = $layerObj->getGDHandle();
    }
}