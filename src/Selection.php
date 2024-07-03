<?php

namespace Naomai\PHPLayers; 

class Selection{
    protected \GdImage $gdImage;
    protected ?\GdImage $subImage = null;
    protected int $offsetX;
    protected int $offsetY;
    protected int $sizeX;
    protected int $sizeY;
    protected int $offsetXorig;
    protected int $offsetYorig;
    protected int $sizeXorig;
    protected int $sizeYorig;
    protected Filters\FilterBase $filterX;
    protected PaintTools\ToolsBase $paintX;

    public function __construct(&$image, $x, $y, $w, $h) {
        $this->gdImage = &$image;
        $this->offsetX = $x;
        $this->offsetY = $y;
        $this->sizeX   = $w;
        $this->sizeY   = $h;
        $this->copyOriginalSelectionDimensions();

        $this->filterX = new Filters\PHPFilters();
        $this->paintX = new PaintTools\DefaultTools();
    }
    
    public function __destruct() {
        if($this->subImage !== null) {
            imagedestroy($this->subImage);
        }
    }
    
    public function __get($v) {
        if($v=="filter") {
            $this->transformationStart();
            return $this->filterX;
        } elseif($v=="paint") {
            $this->transformationStart();
            return $this->paintX;
        }
    }
    
    protected function createSubImage() {
        $this->subImage = imagecreatetruecolor($this->sizeX, $this->sizeY);
        imagealphablending($this->subImage, false);
        imagecopy(
            $this->subImage, $this->gdImage, 
            0, 0, 
            $this->offsetX, $this->offsetY, 
            $this->sizeX, $this->sizeY
        );
        $this->filterX->attachToGD($this->subImage);
        $this->paintX->attachToGD($this->subImage);
    }
    protected function blankSourceSelectionRect() {
        imagealphablending($this->gdImage, false);
        imagefilledrectangle(
            $this->gdImage,
            $this->offsetXorig, $this->offsetYorig,
            $this->offsetXorig+$this->sizeXorig-1, $this->offsetYorig+$this->sizeYorig-1,
            0x7F000000
        );
        imagealphablending($this->gdImage, true);
    }
    protected function applySubImage() {
        imagecopy(
            $this->gdImage, $this->subImage, 
            $this->offsetX, $this->offsetY, 
            0, 0, 
            $this->sizeX, $this->sizeY
        );
    }
    
    protected function transformationStart() {
        if($this->subImage === null) {
            $this->createSubImage();
        }
    }
    protected function transformationEnd() {
        if($this->subImage !== null) {
            $this->blankSourceSelectionRect();
            $this->applySubImage();
            imagedestroy($this->subImage);
            $this->subImage = null;
            $this->copyOriginalSelectionDimensions();
        }
    }
    protected function copyOriginalSelectionDimensions() {
        $this->offsetXorig = $this->offsetX;
        $this->offsetYorig = $this->offsetY;
        $this->sizeXorig   = $this->sizeX;
        $this->sizeYorig   = $this->sizeY;
    }
    
    public function fill($color) {
        imagealphablending($this->subImage, true);
        imagefilledrectangle(
            $this->subImage,
            0, 0,
            $this->sizeX-1, $this->sizeY-1,
            $color
        );
    }
    public function fillOverwrite($color) {
        imagealphablending($this->subImage, false);
        imagefilledrectangle(
            $this->subImage,
            0, 0,
            $this->sizeX-1, $this->sizeY-1,
            $color
        );
        imagealphablending($this->subImage, true);
    }
    public function floodFill($x,$y,$color) {
        imagealphablending($this->subImage, true);
        imagefill($this->subImage, $x, $y, $color);
    }	
    public function floodFillOverwrite($x,$y,$color) {
        imagealphablending($this->subImage, false);
        imagefill($this->subImage, $x, $y, $color);
        imagealphablending($this->subImage, true);
    }


    
    /* transformations */
    public function move($x,$y) {
        
        $this->transformationStart();
        if($x==IMAGE_RIGHT) {
            $x = imagesx($this->gdImage) - $this->sizeX;
        }
        if($y==IMAGE_BOTTOM) {
            $y = imagesy($this->gdImage) - $this->sizeY;
        }
        $this->offsetX = $x;
        $this->offsetY = $y;
        return $this;
    }
    public function moveOffset($ox, $oy) {
        $this->transformationStart();
        $this->offsetX += $ox;
        $this->offsetY += $oy;
        return $this;
    }
    public function resize($w, $h) {
        $this->transformationStart();
        $newSubImage = imagecreatetruecolor($w, $h);
        imagecopyresampled(
            $newSubImage, $this->subImage,
            0, 0,
            0, 0,
            $w, $h,
            $this->sizeX, $this->sizeY
        );
        imagedestroy($this->subImage);
        $this->subImage = $newSubImage;
        $this->sizeX=$w;
        $this->sizeY=$h;
        return $this;
    }
    /* PHP5.5+ */
    public function rotate($degrees) {
        if(!GDIMAGE_SUPPORTS_AFFINE) {
            throw new \RuntimeException(
                "rotate function requires imageaffine support from PHP 5.5+"
            );
        }
        $this->transformationStart();
        $sind = sin($degrees/180*M_PI);
        $cosd = cos($degrees/180*M_PI);
        
        $newSubImage = imageaffine(
            $this->subImage,
            [$cosd, $sind, -$sind, $cosd, 0, 0]
        );
        
        $this->offsetX += $this->sizeX/2 - imagesx($newSubImage)/2;
        $this->offsetY += $this->sizeY/2 - imagesy($newSubImage)/2;
        $this->sizeX = imagesx($newSubImage);
        $this->sizeY = imagesy($newSubImage);
        imagedestroy($this->subImage);
        $this->subImage = $newSubImage;
        return $this;
    }
    
    // creates a Clip object with content of selection
    public function copyClip() {
        $this->transformationStart();
        return new Clip($this->subImage);
        
    }
    
    public function pasteClip($clip, $x=0, $y=0) {
        $clipImg = $clip->getContents();
        $this->transformationStart();
        imagecopy(
            $this->subImage, $clipImg,
            $x, $y,
            0, 0,
            imagesx($clipImg), imagesy($clipImg)
        );
    }
    
    public function apply() {
        $this->transformationEnd();
    }
}