<?php

namespace Naomai\PHPLayers\Generators;

use Naomai\PHPLayers;
use Naomai\PHPLayers\Layer;

class NonOverlappingText implements ILayerGenerator {
    public int $color = 0xFF0000;
    public int $spacing = 1;
    protected Layer $layer;
    protected array $labelsList = [];
    
    public function write(int $x, int $y, string $text, ...$params) {
        $newLabel = ['x'=>$x, 'y'=>$y, 'text'=>$text, 'params'=>$params];
        $this->labelsList[]=$newLabel;
    }
    
    public function attachLayer($layerObj) {
        $this->layer = $layerObj;
    }
    public function apply() {
        $layerPainter = $this->layer->paint()->once();
        foreach($this->labelsList as $labelId=>$label){
            $params = self::filterParamsForMethod(PHPLayers\Painter::class, 'textGetBox', $label['params']);
            $rect = $layerPainter->textGetBox(
                round($label['x']), round($label['y']),
                $label['text'], ...$params
            );
            $this->labelsList[$labelId]['w'] = $rect['w']+$this->spacing;
            $this->labelsList[$labelId]['h'] = $rect['h']+$this->spacing;
            $this->labelsList[$labelId]['area'] = $rect['w'] * $rect['h'];
        }
        
        $this->spaceOutLabels();
        
        foreach($this->labelsList as $labelId=>$label) {
            $params = self::filterParamsForMethod(PHPLayers\Painter::class, 'text', $label['params']);
            $layerPainter->text(
                round($label['x']), round($label['y']),
                $label['text'], ...$params
            );
        }
    }
    
    
    protected function spaceOutLabels() {
        //based on: http://stackoverflow.com/a/3279877

        while($intersections = $this->getSortedIntersectingRects()){
            $label = $this->labelsList[$intersections[0]['labelid']];
            unset($this->labelsList[$intersections[0]['labelid']]);

            $rectsInt = $this->getIntersectionsForRectangle($label);

            $grpMinX = 0xFFFFFFFF;
            $grpMinY = 0xFFFFFFFF;
            $grpMaxX = 0;
            $grpMaxY = 0;
            
            array_push($rectsInt, $label);
            foreach($rectsInt as $rect){
                $grpMinX = min($grpMinX, $rect['x']);
                $grpMaxX = max($grpMaxX, $rect['x']+$rect['w']);
                $grpMinY = min($grpMinY, $rect['y']);
                $grpMaxY = max($grpMaxY, $rect['y']+$rect['h']);
            }
            array_pop($rectsInt);

            $grpCenterX = ($grpMinX + $grpMaxX) / 2;
            $grpCenterY = ($grpMinY + $grpMaxY) / 2;
            
            $rectCenterX = $label['x']  +$label['w']/2 ;
            $rectCenterY = $label['y']  +$label['h']/2 ;
            
            $vecX = $rectCenterX - $grpCenterX;
            $vecY = $rectCenterY - $grpCenterY;

            if($vecX==0 && $vecY==0) {
                $vecX=mt_rand(1, 10)/10;
                $vecY=mt_rand(1, 10)/10;
            }
            $div = max(abs($vecX), abs($vecY));
            if($div>1.5) {
                $div = 1.5;
            }
            
            $moveX = $vecX / $div;
            $moveY = $vecY / $div;
            
            $label['x'] += $moveX;
            $label['y'] += $moveY;
            
            $grpNewMinX = min($grpMinX, $label['x']);
            $grpNewMaxX = max($grpMaxX, $label['x']+$label['w']);
            $grpNewMinY = min($grpMinY, $label['y']);
            $grpNewMaxY = max($grpMaxY, $label['y']+$label['h']);
            
            $grpNewCenterX = ($grpNewMinX + $grpNewMaxX) / 2;
            $grpNewCenterY = ($grpNewMinY + $grpNewMaxY) / 2;
            
            $grpOffsetX = $grpNewCenterX - $grpCenterX;
            $grpOffsetY = $grpNewCenterY - $grpCenterY;
            
            foreach($rectsInt as $rectId=>$rect){
                $this->labelsList[$rectId]['x']-=$grpOffsetX;
                $this->labelsList[$rectId]['y']-=$grpOffsetY;
            }
            $label['x']-=$grpOffsetX;
            $label['y']-=$grpOffsetY;

            array_push($this->labelsList, $label);
            
        }

    }
    
    protected function areRectanglesIntersecting() {
        foreach($this->labelsList as $label){
            if($this->checkIntersectionsForRectangle($label)) {
                return true;
            }
        }
        return false;
    }
    
    protected function getIntersectionsForRectangle($rect) {
        $intersections = array();

        foreach($this->labelsList as $labelId=>$label) {
            if($rect==$label) {
                continue;
            }
            if(self::checkIntersection($label, $rect)) {
                $intersections[$labelId] = $label;
            }
        }
        return $intersections;
    }
    
    protected function checkIntersectionsForRectangle($rect) {
        foreach($this->labelsList as $label) {
            if($rect==$label) {
                continue;
            }
            if(self::checkIntersection($label, $rect)) {
                return true;
            }
        }
        return false;
    }
    
    protected function getSortedIntersectingRects() {
        $intersections = [];
        foreach($this->labelsList as $labelId=>$label) {
            $labelInt = $this->getIntersectionsForRectangle($label);
            if(!count($labelInt)) {
                continue;
            }
            $intersections[] = ['labelid'=>$labelId,'intersections'=>count($labelInt)];
        }
        usort(
            $intersections, 
            fn($a, $b) => ($a['intersections']<=>$b['intersections'])
        );
        return $intersections;
    }
    
    protected static function checkIntersection($r1, $r2) {
        return max($r1['x'], $r2['x'])<min($r1['x']+$r1['w'], $r2['x']+$r2['w'])
            && max($r1['y'], $r2['y'])<min($r1['y']+$r1['h'], $r2['y']+$r2['h']);
    }

    
    static function filterParamsForMethod($class, $method, $params) {
        $reflection = new \ReflectionMethod($class, $method);
        $allowedParams = $reflection->getParameters();

        $result = [];
        foreach($allowedParams as $paramReflection){
            $paramName = $paramReflection->name;
            if(isset($params[$paramName])) {
                $result[$paramName] = $params[$paramName];
            }
        }
        return $result;

    }
}
