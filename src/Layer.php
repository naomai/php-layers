<?php

namespace Naomai\PHPLayers;

class Layer {
    protected ?\GdImage $gdImage = null;
    
    /** 
     * Width of the layer buffer 
     * */
    protected ?int $sizeX = null;

    /** 
     * Height of the layer buffer 
     * */
    protected ?int $sizeY = null;

    /** 
     * Width of the layer surface
     * 
     * When imported from file, this will be the width of source image
     * Used for selectWhole function with Edge Positioning
     * */
    protected int $sourceSizeX;

    /** 
     * Height of the layer surface 
     * 
     * When imported from file, this will be the height of source image
     * Used for selectWhole function with Edge Positioning
     * */
    protected int $sourceSizeY;

    /** 
     * Position of Layer Surface on the destination image (X coordinate) 
     * 
     * Used to track the position of source image on layer
     */
    public int $offsetX=0;

    /** 
     * Position of Layer Surface on the destination image (Y coordinate) 
     * 
     * Used to track the position of source image on layer
     */
    public int $offsetY=0;

    /** 
     * Layer name 
     * */
    public string $name = "";
    
    /** 
     * Blending type 
     */
    protected int $blending = Layer::GDLAYER_BLEND_NORMAL;

    /** 
     *  Layer opacity in percent
     * 0=transparent, 100=fully opaque
     * */
    protected float $opacity = 100;

    /** 
     * Object providing image filters
     */
    public Filters\FilterBase $filter;
    
    /** 
     * Object providing drawing functions
     */
    protected Painter $painter;

    /** 
     * Content generator attached to the layer
     * */
    protected ?Generators\ILayerGenerator $generator = null;

    /**
     * Image object the layer is attached to
     */
    protected ?Image $parentImg = null;
    
    /** TODO Enumeration */
    const GDLAYER_BLEND_NORMAL=0;
        
    public function __construct() {
        $this->setSurfaceDimensions(1, 1);
        $this->gdImage = imagecreatetruecolor(1, 1);
            
        $this->filter = new Filters\PHPFilters($this);
        $this->painter = new Painter($this);
    }
    
    public function __destruct() {
        if($this->gdImage!==null) {
            imagedestroy($this->gdImage);
        }
    }
        
    /**
     * Get dimensions and position of layer surface
     *
     * @return array{x: int, y: int, w: int, h:int}
     */
    public function getSurfaceDimensions() : array {
        return  [
            'x'=>$this->offsetX,
            'y'=>$this->offsetY,
            'w'=>$this->sourceSizeX,
            'h'=>$this->sourceSizeY
        ];
    }

    /**
     * Get dimensions and position of internal layer buffer
     *
     * @return array{x: int, y: int, w: int, h:int}
     */
    public function getDimensions() : array {
        return  [
            'x'=>0,
            'y'=>0,
            'w'=>$this->sizeX,
            'h'=>$this->sizeY
        ];
    }

    public function setSurfaceDimensions(
        int $width, int $height, int $offsetX=0, int $offsetY=0
    ) : Layer {
        $this->offsetX = $offsetX;
        $this->offsetY = $offsetY;
        $this->sourceSizeX = $width;
        $this->sourceSizeY = $height;
        return $this;
    }
        
    /**
     * Get GdImage object of the layer
     *
     * @return \GdImage
     */
    public function getGDHandle() : \GdImage {
        return $this->gdImage;
    }
        
    /**
     * Set opacity of layer in percent
     *
     * @param  float $opacity Opacity (0=transparent, 100=fully opaque)
     */
    public function setOpacity(float $opacity) : void {
        $this->opacity = $opacity;
    }

    /**
     * Get opacity of layer in percent
     *
     * @return  float $opacity Opacity (0=transparent, 100=fully opaque)
     */
    public function getOpacity() : float {
        return $this->opacity;
    }

    /**
     * Get helper object for rearranging layers in Layer Stack
     *
     * @return Helpers\LayerReorderCall  Helper object with reorder operations
     */
    public function reorder() : Helpers\LayerReorderCall {
        if($this->parentImg===null) {
            throw new \RuntimeException("Cannot reorder a layer not attached to image");
        }
        return $this->parentImg->reorder($this);
    }
    
    /* PAINT */    

    /**
     * Return Painter object attached to the layer
     *
     * @return Painter
     */
    public function paint(...$options) : Painter {
        $painter = $this->painter;

        if(count($options)) {
            $painter->setOptions(...$options);
        }
        return $painter;
        
    }

    /**
     * Cover entire layer buffer with $color, discarding previous content.
     * Effectively, replaces every pixel of layer.
     * Not to be confused with Flood Fill. 
     *
     * @param  int $color
     */
    public function fill(int $color) : void {
        imagealphablending($this->gdImage, false);
        imagefilledrectangle(
            $this->gdImage, 
            0, 0, 
            $this->sizeX-1, $this->sizeY-1, 
            $color
        );
        imagealphablending($this->gdImage, true);
    }
    
    /**
     * Clears layer buffer. The layer content is fully wiped, 
     * resulting in fully transparent surface. 
     */
    public function clear() : void {
        $this->fill(0x7F000000);
    }
    
    /* SELECT */    
    /**
     * Select entire area of layer buffer
     *
     * @return Selection Helper object for transforming selection
     */
    public function selectWhole() : Selection {
        if($this->sizeX===null) {
            throw new \RuntimeException("Layer surface does not exist yet");
        }
        $x=0;
        $y=0;
        $w=$this->sizeX;
        $h=$this->sizeY;
        return $this->select($x, $y, $w, $h);
    }

    /**
     * Select Layer Surface area
     *
     * @return Selection Helper object for transforming selection
     */
    public function selectSurface() : Selection {
        $x=$this->offsetX;
        $y=$this->offsetY;
        $w=$this->sourceSizeX;
        $h=$this->sourceSizeY;
        return $this->select($x, $y, $w, $h);
    }
    /**
     * Create selection 
     * 
     * @param  int $x Horizontal position of selection, relative to image
     * @param  int $y Vertical position of selection, relative to image
     * @param  int $w Width of selection
     * @param  int $h Height of selection
     *
     * @return Selection Helper object for transforming selection
     */    
    public function select(int $x, int $y, int $w, int $h) : Selection {
        return new Selection($this, $x, $y, $w, $h);
    }
    
    public function pasteClip(Clip $clip, int $x=0, int $y=0) : void {
        $clipImg = $clip->getContents();
        imagecopy(
            $this->gdImage, $clipImg, 
            $x, $y, 
            0, 0, 
            imagesx($clipImg), imagesy($clipImg)
        );
    }
    
    public function transformPermanently() : void {
        if($this->parentImg===null) {
            $newSize = $this->getDimensions();
        }else{
            $newSize = $this->parentImg->getSize();
        }
        $newLayerGD = imagecreatetruecolor($newSize['w'], $newSize['h']);
        imagealphablending($newLayerGD, false);
        imagefill($newLayerGD, 0, 0, 0x7F000000);
        imagecopy(
            $newLayerGD, $this->gdImage, 
            //$this->offsetX, $this->offsetY, 
            0, 0,
            0, 0, 
            imagesx($this->gdImage), imagesy($this->gdImage)
        ); 
        imagedestroy($this->gdImage);
        $this->gdImage = $newLayerGD;
        //$this->offsetX = $this->offsetY = 0;
        $this->sizeX = $newSize['w'];
        $this->sizeY = $newSize['h'];
        $this->painter->attachToLayer($this);
        $this->filter->attachToLayer($this);
    }
    
    public function setParentImg(Image $parentImg) : void {
        $this->parentImg = $parentImg;
        $this->transformPermanently();
    }

    public function getParentImg() : ?Image {
        return $this->parentImg;
    }
    
    
    public function setGenerator(Generators\ILayerGenerator $generator) : void {
        $generator->attachLayer($this);
        $this->generator = $generator;
    }

    public function render() : void {
        if($this->generator !== null) {
            $this->generator->apply();
        }
    }

    public function importFromGD(\GdImage $gdSource) : Layer {
        $this->gdImage = $gdSource;
        $this->sizeX = imagesx($this->gdImage);
        $this->sizeY = imagesy($this->gdImage);
        $this->setSurfaceDimensions($this->sizeX, $this->sizeY);
        $this->transformPermanently();
        return $this;
    }

    public function importFromFile(string $fileName) : Layer {
        if(!file_exists($fileName)) {
            throw new \RuntimeException("File not found: ".$fileName);
        }
        $gdSource = @imagecreatefromstring(file_get_contents($fileName));
        if($gdSource===false){
            throw new \RuntimeException("Invalid or malformed image file: ".$fileName);
        }
        $this->importFromGD($gdSource);
        $this->name = basename($fileName);
        return $this;
    }


}