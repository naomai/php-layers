<?php

namespace Naomai\PHPLayers; 

class LayerStack {
    protected $layers = []; // 0=bottom

    public function putAt(int $indexNew, Layer $layerToPut) : void {
        if($indexNew < 0) {
            $indexNew = $this->getCount() + $indexNew;
        }
        if($indexNew < 0) {
            throw new \InvalidArgumentException("Negative index out of bound");
        }

        $this->remove($layerToPut);
        array_splice($this->layers, $indexNew, 0, [$layerToPut]);
    }

    public function remove(Layer $layer) : bool {
        $indexCurrent = $this->getIndexOf($layer);
        if($indexCurrent !== false) {
            array_splice($this->layers, $indexCurrent, 1);
            return true;
        }
        return false;
    }

    public function getIndexOf(Layer $layer) : int|bool {
        $layerId = array_search($layer, $this->layers, true);
        if($layerId === false) {
            return false;
        }
        $layerIndex = array_search($layerId, array_keys($this->layers));
        return $layerIndex;
    }

    public function getLayerByIndex(int $index) : ?Layer {
        if(!isset($this->layers[$index])) {
            return null;
        }
        return $this->layers[$index];
    }

    public function getCount() : int {
        return count($this->layers);
    }

    public function getAll() : array {
        return $this->layers;
    }


}