<?php
namespace Naomai\PHPLayers\Composers;

use Naomai\PHPLayers;
use Naomai\PHPLayers\Layer;
use Naomai\PHPLayers\LayerStack;
use Naomai\PHPLayers\Image;
use Naomai\PHPLayers\Renderers\ILayerRenderer;

abstract class LayerComposerBase  {
    protected LayerStack $layers;
    protected Image $image;
    
    public function __construct() {

    }
    
    public function setLayerStack(LayerStack $layers) : void {
        $this->layers = $layers;
    }
    
    public function preprocessLayer(Layer $layerObj) : void {
        $layerObj->render();
    }

    public function setImage(Image $image) : void {
        $this->image = $image;
    }


    abstract public function mergeAll() : Layer; 
}