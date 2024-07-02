<?php

namespace Naomai\PHPLayers\Helpers; 

use Naomai\PHPLayers\LayerStack;
use Naomai\PHPLayers\Layer;

class LayerReorderCall {
    protected LayerStack $layerStack;
    protected Layer $layerToMove;

    public function __construct(LayerStack $layerStack) {
        $this->layerStack = $layerStack;
    }

    public function setLayerToMove(Layer $layer) : void {
        $this->layerToMove = $layer;
    }

    public function getLayerStack() : LayerStack {
        return $this->layerStack;
    }

    public function putAt(int $indexNew) : void {
        $this->layerStack->putAt($indexNew, $this->layerToMove);
    }

    public function putTop() : void {
        $indexNew = $this->layerStack->getCount();
        $this->putAt($indexNew);
    }

    public function putBottom() : void {
        $indexNew = 0;
        $this->putAt($indexNew);
    }

    public function putOver(Layer $layerTarget) : void {
        $indexOfTarget = $this->layerStack->getIndexOf($layerTarget);
        if($indexOfTarget === false) {
            return; // TODO decide on handling this case
        }
        $indexNew = $indexOfTarget + 1;
        $this->putAt($indexNew);
    }

    public function putBehind(Layer $layerTarget) : void {
        $indexOfTarget = $this->layerStack->getIndexOf($layerTarget);
        if($indexOfTarget === false) {
            return; // TODO decide on handling this case
        }
        $indexNew = $indexOfTarget;
        $this->putAt($indexNew);
    }


}