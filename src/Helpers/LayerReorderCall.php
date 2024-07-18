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
     * @return int Actual index of inserted layer on LayerStack
     */
    public function putAt(int $indexNew) : int {
        $indexActual = $this->layerStack->putAt($indexNew, $this->layerToMove);
        return $indexActual;
    }

    /**
     * Inserts layerToMove at the top of Layer Stack.
     * If the layerToMove is already on the stack, 
     * it's pulled from its place beforehand.
     * 
     * @return int Actual index of inserted layer on LayerStack
     */
    public function putTop() : int {
        $indexNew = $this->layerStack->getCount();
        return $this->putAt($indexNew);
    }
    
    /**
     * Inserts layerToMove at the bottom of Layer Stack.
     * If the layerToMove is already on the stack, 
     * it's pulled from its place beforehand.
     * @return int Actual index of inserted layer on LayerStack
     */
    public function putBottom() : int {
        $indexNew = 0;
        return $this->putAt($indexNew);
    }

    /**
     * Inserts layerToMove on top of provided layerTarget.
     * If the layerToMove is already on the stack, 
     * it's pulled from its place beforehand.
     * 
     * @param Layer $layerTarget
     * @return ?int Actual index of inserted layer on LayerStack
     */
    public function putOver(Layer $layerTarget) : ?int {
        $indexOfTarget = $this->layerStack->getIndexOf($layerTarget);
        if($indexOfTarget === false) {
            throw new \InvalidArgumentException(
                "Target layer not attached to the same image as layer to move"
            );
        }
        $indexNew = $indexOfTarget + 1;
        return $this->putAt($indexNew);
    }

    /**
     * Inserts layerToMove below provided layerTarget.
     * If the layerToMove is already on the stack, 
     * it's pulled from its place beforehand.
     * 
     * @param Layer $layerTarget
     * @return ?int Actual index of inserted layer on LayerStack
     */
    public function putBehind(Layer $layerTarget) : ?int {
        $indexOfTarget = $this->layerStack->getIndexOf($layerTarget);
        if($indexOfTarget === false) {
            throw new \InvalidArgumentException(
                "Target layer not attached to the same image as layer to move"
            );
        }
        $indexNew = $indexOfTarget;
        return $this->putAt($indexNew);
    }

    public function getLayerStack() : LayerStack {
        return $this->layerStack;
    }

    public function setLayerToMove(Layer $layer) : void {
        $this->layerToMove = $layer;
    }
}