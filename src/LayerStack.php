<?php

namespace Naomai\PHPLayers; 

class LayerStack {
    protected $layers = []; // 0=bottom

    /**
     * Moves layerToPut into given position on the Layer Stack.
     * If the layerToPut is already on the stack, 
     * it's pulled from its place beforehand. 
     * The layer is inserted into the position AFTER stack collapsing.
     * Actual index of inserted layer is the same as provided.
     *
     * @param  mixed $indexNew   Zero-based position 
     * @param  Layer $layerToPut Layer to be inserted
     * @return int Actual index of inserted layer on LayerStack
     */
    public function putAt(int $indexNew, Layer $layerToPut) : int {
        $layersCount = $this->getCount();
        if($indexNew < 0) {
            $indexNew = $layersCount + $indexNew;
        }
        if($indexNew < 0) {
            throw new \InvalidArgumentException("Negative index out of bound");
        }

        $this->remove($layerToPut);
        array_splice($this->layers, $indexNew, 0, [$layerToPut]);

        $indexActual = $this->getIndexOf($layerToPut);

        return $indexActual;
    }

    /**
     * Inserts layerToPut behind element at given position
     * on the Layer Stack.
     * If the layerToPut is already on the stack, 
     * it's pulled from its place beforehand. 
     * The layer is inserted into the position BEFORE stack collapsing.
     * As a result, the actual index of layerToPut might be different.
     *
     * @param  mixed $indexNew   Zero-based position 
     * @param  Layer $layerToPut Layer to be inserted
     * @return int Actual index of inserted layer on LayerStack
     */
    public function putBehind(int $indexNew, Layer $layerToPut) : int {
        $layersCount = $this->getCount();
        if($indexNew < 0) {
            $indexNew = $layersCount + $indexNew;
        }
        if($indexNew < 0) {
            throw new \InvalidArgumentException("Negative index out of bound");
        }

        $indexCurrent = $this->getIndexOf($layerToPut);
        if($indexCurrent == $indexNew) {
            return $indexNew;
        }
        $isStackCollapingBeforeDestination 
            = $indexCurrent!==false && $indexNew > $indexCurrent;

        $this->remove($layerToPut);
        if($isStackCollapingBeforeDestination) {
            $indexNew -= 1;
        }
        array_splice($this->layers, $indexNew, 0, [$layerToPut]);

        $indexActual = $this->getIndexOf($layerToPut);

        return $indexActual;
    }

    /**
     * Removes given layer object from the Layer Stack
     *
     * @param  Layer $layer  Layer to be removed
     */
    public function remove(Layer $layer) : bool {
        $indexCurrent = $this->getIndexOf($layer);
        if($indexCurrent !== false) {
            array_splice($this->layers, $indexCurrent, 1);
            return true;
        }
        return false;
    }

    /**
     * Retrieves index of given layer in Layer Stack
     *
     * @param  Layer $layer  Layer for index lookup
     */
    public function getIndexOf(Layer $layer) : int|bool {
        $layerId = array_search($layer, $this->layers, true);
        if($layerId === false) {
            return false;
        }
        $layerIndex = array_search($layerId, array_keys($this->layers));
        return $layerIndex;
    }

    /**
     * Retrieves layer of given index from Layer Stack
     *
     * @param  int $index  Index of layer.
     *                     If negative, count from the last layer.
     * @retyrb ?Layer      Layer matching given index, or null if not found
     */
    public function getLayerByIndex(int $index) : ?Layer {
        $layersCount = $this->getCount();
        if($index < 0) {
            $index = $layersCount + $index;
        }
        if($index < 0) {
            return null;
        }

        if(!isset($this->layers[$index])) {
            return null;
        }
        return $this->layers[$index];
    }

    /**
     * Retrieves number of all layers on Layer Stack
     *
     * @return int Total number of layers on the stack
     */
    public function getCount() : int {
        return count($this->layers);
    }

    /**
     * Retrieves all layers on the stack
     *
     * @return Layer[]  All layers in bottom-to-top order
     */
    public function getAll() : array {
        return $this->layers;
    }


}