<?php

namespace Naomai\PHPLayers; 

class Selection{
    protected Layer $layer;
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
    protected Painter $painter;

    public function __construct(Layer $layer, int $x, int $y, int $w, int $h) {
        $this->layer = $layer;
        $this->offsetX = $x;
        $this->offsetY = $y;
        $this->sizeX   = $w;
        $this->sizeY   = $h;
        $this->copyOriginalSelectionDimensions();

        $this->filterX = new Filters\PHPFilters();
        $this->painter = new Painter();
    }
    
    public function __destruct() {
        if($this->subImage !== null) {
            imagedestroy($this->subImage);
        }
    }
    
    // reimplement as methods (#2)
    public function __get(string $v) {
        if($v=="filter") {
            $this->transformationStart();
            return $this->filterX;
        }
    }
    
    protected function createSubImage() {
        $this->subImage = imagecreatetruecolor($this->sizeX, $this->sizeY);
        imagealphablending($this->subImage, false);
        imagecopy(
            $this->subImage, $this->layer->getGDHandle(), 
            0, 0, 
            $this->offsetX, $this->offsetY, 
            $this->sizeX, $this->sizeY
        );
        $this->filterX->attachToGD($this->subImage);
        $this->painter->attachToGD($this->subImage);
    }

    public function getCurrentRect() : array {
        return [
            'w'=>$this->sizeX, 'h'=>$this->sizeY,
            'x'=>$this->offsetX, 'y'=>$this->offsetY
        ];
    }

    protected function blankSourceSelectionRect() {
        $layerGD = $this->layer->getGDHandle();
        imagealphablending($layerGD, false);
        imagefilledrectangle(
            $layerGD,
            $this->offsetXorig, $this->offsetYorig,
            $this->offsetXorig+$this->sizeXorig-1, $this->offsetYorig+$this->sizeYorig-1,
            0x7F000000
        );
        imagealphablending($layerGD, true);
    }

    protected function applySubImage() {
        $layerGD = $this->layer->getGDHandle();
        imagecopy(
            $layerGD, $this->subImage, 
            $this->offsetX, $this->offsetY, 
            0, 0, 
            $this->sizeX, $this->sizeY
        );

        $imageDimensions = $this->layer->getDimensions();

        // drop Layer Surface coords
        $this->layer->setSurfaceDimensions($imageDimensions['w'], $imageDimensions['h'], 0, 0);
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
    
    public function fill(int $color) {
        imagealphablending($this->subImage, true);
        imagefilledrectangle(
            $this->subImage,
            0, 0,
            $this->sizeX-1, $this->sizeY-1,
            $color
        );
    }
    public function fillOverwrite(int $color) {
        imagealphablending($this->subImage, false);
        imagefilledrectangle(
            $this->subImage,
            0, 0,
            $this->sizeX-1, $this->sizeY-1,
            $color
        );
        imagealphablending($this->subImage, true);
    }
    public function floodFill(int $x, int $y, int $color) {
        imagealphablending($this->subImage, true);
        imagefill($this->subImage, $x, $y, $color);
    }	
    public function floodFillOverwrite($x,$y,$color) {
        imagealphablending($this->subImage, false);
        imagefill($this->subImage, $x, $y, $color);
        imagealphablending($this->subImage, true);
    }

    /* transformations */
    public function move(
        ?int $x=0, ?int $y=0, 
        string $anchor="top left"
    ) : Selection {
        $this->transformationStart();

        $layerDimensions = $this->layer->getDimensions();
        $anchorDefs = explode(" ", $anchor);
        if(in_array("right", $anchorDefs)) {
            $x += $layerDimensions['w'] - $this->sizeX;
        }
        if(in_array("bottom", $anchorDefs)) {
            $y += $layerDimensions['h'] - $this->sizeY;
        }

        $this->offsetX = $x;
        $this->offsetY = $y;
        return $this;
    }
    public function moveOffset(int $ox, int $oy) : Selection {
        $this->transformationStart();
        $this->offsetX += $ox;
        $this->offsetY += $oy;
        return $this;
    }
    public function resize(int $w, int $h) : Selection {
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

    public function rotate(float $degrees) : Selection {
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
    public function copyClip() : Clip {
        $this->transformationStart();
        return new Clip($this->subImage);
        
    }
    
    public function pasteClip(Clip $clip, int $x=0, int $y=0) {
        $clipImg = $clip->getContents();
        $this->transformationStart();
        imagecopy(
            $this->subImage, $clipImg,
            $x, $y,
            0, 0,
            imagesx($clipImg), imagesy($clipImg)
        );
    }

    /**
     * Return Painter object attached to the selection
     *
     * @return \Painter
     */
    public function paint(bool $oneShot=false, ...$options) : Painter {
        $this->transformationStart();
        $painter = $this->painter;
        if($oneShot) {
            $painter = new Painter();
            
        }

        if(count($options)) {
            $painter->setOptions(...$options);
        }

        $painter->attachToGD($this->subImage);
        return $painter;
        
    }
    
    public function apply() {
        $this->transformationEnd();
    }
}