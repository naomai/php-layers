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
    
    /**
     * Inserts layerToMove into given position on the Layer Stack.
     * If the layerToMove is already on the stack, 
     * it's pulled from its place beforehand.
     *
     * @param  mixed $indexNew Zero-based position 
     */
    public function putAt(int $indexNew) : void {
        $this->layerStack->putAt($indexNew, $this->layerToMove);
    }

    /**
     * Inserts layerToMove at the top of Layer Stack.
     * If the layerToMove is already on the stack, 
     * it's pulled from its place beforehand.
     */
    public function putTop() : void {
        $indexNew = $this->layerStack->getCount();
        $this->putAt($indexNew);
    }
    
    /**
     * Inserts layerToMove at the bottom of Layer Stack.
     * If the layerToMove is already on the stack, 
     * it's pulled from its place beforehand.
     */
    public function putBottom() : void {
        $indexNew = 0;
        $this->putAt($indexNew);
    }

    /**
     * Inserts layerToMove on top of provided layerTarget.
     * If the layerToMove is already on the stack, 
     * it's pulled from its place beforehand.
     * 
     * @param Layer $layerTarget
     */
    public function putOver(Layer $layerTarget) : void {
        $indexOfTarget = $this->layerStack->getIndexOf($layerTarget);
        if($indexOfTarget === false) {
            return; // TODO decide on handling this case
        }
        $indexNew = $indexOfTarget + 1;
        $this->putAt($indexNew);
    }

    /**
     * Inserts layerToMove below provided layerTarget.
     * If the layerToMove is already on the stack, 
     * it's pulled from its place beforehand.
     * 
     * @param Layer $layerTarget
     */
    public function putBehind(Layer $layerTarget) : void {
        $indexOfTarget = $this->layerStack->getIndexOf($layerTarget);
        if($indexOfTarget === false) {
            return; // TODO decide on handling this case
        }
        $indexNew = $indexOfTarget;
        $this->putAt($indexNew);
    }

    public function getLayerStack() : LayerStack {
        return $this->layerStack;
    }

    public function setLayerToMove(Layer $layer) : void {
        $this->layerToMove = $layer;
    }
}