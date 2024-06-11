<?php
namespace Naomai\PHPLayers\Renderers;

interface ILayerRenderer{
    public function attachLayer($layerObj);
    public function apply();

}
