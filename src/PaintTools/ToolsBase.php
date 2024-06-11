<?php
namespace Naomai\PHPLayers\PaintTools;

define('GDRECT_BORDER', 1);
define('GDRECT_FILLED', 2);
define('GDRECT_FILLEDBORDER', GDRECT_BORDER|GDRECT_FILLED);
define('GDALIGN_LEFT', 0);
define('GDALIGN_CENTER', 1);
define('GDALIGN_RIGHT', 2);

define('GDCOLOR_DEFAULT', -1);

abstract class ToolsBase{
    protected $destLayer;
    protected $destGD;
    
    public function __construct($layerObj=null){
        if($layerObj !== null)
            $this->attachToLayer($layerObj);
    }
    
    public function attachToLayer($layerObj){
        $this->destLayer = $layerObj;
        $this->destGD = $layerObj->getGDHandle();
    }
}