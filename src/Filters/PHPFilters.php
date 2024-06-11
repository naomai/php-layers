<?php
namespace Naomai\PHPLayers\Filters;
use Naomai\PHPLayers as GDW;

class PHPFilters extends FilterBase{


    public final function gdFilter(){
        $args = func_get_args();
        array_unshift($args,$this->destGD);
        call_user_func_array("imagefilter", $args);
    }
    
    public function updateGDSource($gdResource){
        $this->destGD = &$gdResource;
    }
    
    
    /* filter defs */
    public function invert(){
        $this->gdFilter(IMG_FILTER_NEGATE);
    }
    public function grayscale(){
        $this->gdFilter(IMG_FILTER_GRAYSCALE);
    }
    public function brightness($level){
        $level = GDW\clamp_int($level, -255, 255);
        $this->gdFilter(IMG_FILTER_BRIGHTNESS,$level);
    }
    public function contrast($level){
        $level = GDW\clamp_int($level, -100, 100);
        $this->gdFilter(IMG_FILTER_CONTRAST,-$level);
    }
    // don't trust the php docs on this one - no IMG_FILTER_GRAYSCALE involved
    public function colorize($addR=0,$addG=0,$addB=0,$addA=0){
        $addR = GDW\clamp_int($addR, -255, 255);
        $addG = GDW\clamp_int($addG, -255, 255);
        $addB = GDW\clamp_int($addB, -255, 255);
        $addA = GDW\clamp_int($addA, 0, 127);
        $this->gdFilter(IMG_FILTER_COLORIZE,$addR,$addG,$addB,$addA);
    }
    public function edge(){
        $this->gdFilter(IMG_FILTER_EDGEDETECT);
    }
    public function emboss(){
        $this->gdFilter(IMG_FILTER_EMBOSS);
    }
    public function blur(){
        $this->gdFilter(IMG_FILTER_GAUSSIAN_BLUR);
    }
    public function blurSelective(){
        $this->gdFilter(IMG_FILTER_SELECTIVE_BLUR);
    }
    public function sketch(){
        $this->gdFilter(IMG_FILTER_MEAN_REMOVAL);
    }
    public function smooth($weight=1.0){
        $this->gdFilter(IMG_FILTER_SMOOTH,$weight);
    }
    public function pixelate($size=2,$advanced=false){
        $this->gdFilter(IMG_FILTER_PIXELATE,$size,$advanced);
    }
}