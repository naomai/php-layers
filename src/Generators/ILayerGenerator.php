<?php
namespace Naomai\PHPLayers\Generators;

interface ILayerGenerator{
    public function attachLayer($layerObj);
    public function apply();

}
