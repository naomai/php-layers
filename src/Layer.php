<?php

namespace Naomai\PHPLayers;

class Layer {
    protected $gdImage = null;
    
    /** 
     * Position on the destination image (X coordinate) 
     */
    public int $offsetX=0;

    /** 
     *  Position on the destination image (Y coordinate) 
     */
    public int $offsetY=0;

    /** 
     * Width of the image 
     * */
    protected int $sizeX;

    /** 
     * Height of the image 
     * */
    protected int $sizeY;

    /** 
     * Width of the layer surface 
     * */
    protected int $sourceSizeX;

    /** 
     * Height of the layer surface 
     * */
    protected int $sourceSizeY;

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
    protected int $opacity = 100;

    /** 
     * Object providing image filters
     */
    public Filters\FilterBase $filter;
    
    /** 
     * Object providing drawing functions
     */
    public PaintTools\ToolsBase $paint;

    /** 
     * Image preprocessor used before merging with other layers
     * */
    public ?Renderers\ILayerRenderer $renderer = null;

    /**
     * Image object the layer is attached to
     */
    protected Image $parentImg;
    
    /** TODO Enumeration */
    const GDLAYER_BLEND_NORMAL=0;
        
    public function __construct() {
        $args = func_get_args();
        if(count($args) === 1 
            && Image::isValidGDImage($args[0])
        ) { // (resource $img)
            $this->gdImage = $args[0];

        } elseif(count($args) >= 2 
            && is_numeric($args[0]) 
            && is_numeric($args[1])
        ) { // (int $w, int $h)
            $this->gdImage = imagecreatetruecolor($args[0], $args[1]);
            if(count($args) === 4 
                && is_numeric($args[2]) 
                && is_numeric($args[3])
            ) { // (int $w, int $h, int $x, int $y)
                $this->offsetX = $args[2];
                $this->offsetY = $args[3];
            }
        }else{
            throw new \BadFunctionCallException(
                "Layer::__construct requires either 1 or 2 arguments of strictly specified types."
            );
        }
        $this->sizeX = imagesx($this->gdImage);
        $this->sizeY = imagesy($this->gdImage);

        $this->sourceSizeX = $this->sizeX;
        $this->sourceSizeY = $this->sizeY;

        $this->filter = new Filters\PHPFilters($this);
        
        $this->paint = new PaintTools\DefaultTools($this);
    }
    
    public function __destruct() {
        if(Image::isValidGDImage($this->gdImage)) {
            imagedestroy($this->gdImage);
        }
    }
        
    /**
     * Get dimensions and position of layer surface
     *
     * @return array{x: int, y: int, w: int, h:int}
     */
    public function getLayerDimensions() : array {
        return  [
            'x'=>$this->offsetX,
            'y'=>$this->offsetY,
            'w'=>$this->sizeX,
            'h'=>$this->sizeY
        ];
    }
        
    /**
     * Get GdImage object of the layer
     *
     * @return GdImage
     */
    public function getGDHandle() : \GdImage {
        return $this->gdImage;
    }
        
    /**
     * Set opacity of layer in percent
     *
     * @param  int $opacity Opacity (0=transparent, 100=fully opaque
     */
    public function setOpacity(int $opacity) : void {
        $this->opacity = $opacity;
    }

    /**
     * Get opacity of layer in percent
     *
     * @return  int $opacity Opacity (0=transparent, 100=fully opaque)
     */
    public function getOpacity() : int {
        return $this->opacity;
    }

    /**
     * Get helper object for rearranging layers in Layer Stack
     *
     * @return Helpers\LayerReorderCall  Helper object with reorder operations
     */
    public function reorder() : Helpers\LayerReorderCall {
        return $this->parentImg->reorder($this);
    }
    
    /* PAINT */    
    /**
     * Cover entire layer surface with $color, discarding previous content.
     * Effectively, replaces every pixel of layer.
     * Not to be confused with Flood Fill. 
     *
     * @param  int $color
     */
    public function fill(int $color) : void {
        imagealphablending($this->gdImage, false);
        imagefilledrectangle($this->gdImage, 0, 0, $this->sizeX-1, $this->sizeY-1, $color);
        imagealphablending($this->gdImage, true);
    }
    
    /**
     * Clears layer surface. The layer content is fully wiped, 
     * resulting in fully transparent surface. 
     */
    public function clear() : void {
        $this->fill(0x7F000000);
    }
    
    /* SELECT */
    public function select() {
        $args=func_get_args();
        if(count($args) == 4) {
            list($x,$y,$w,$h)=$args;
        } elseif(count($args) == 0) {
            $x=$this->offsetX;
            $y=$this->offsetY;
            $w=$this->sourceSizeX;
            $h=$this->sourceSizeY;
        } else {
            throw new \BadFunctionCallException("Layer::select requires either 0 or 4 arguments.");
        }
        return new Selection($this->gdImage, $x, $y, $w, $h);
    }
    
    public function pasteClip($clip,$x=0,$y=0) {
        $clipImg = $clip->getContents();
        imagecopy($this->gdImage, $clipImg, $x, $y, 0, 0, imagesx($clipImg), imagesy($clipImg));
    }
    
    public function transformPermanently() {
        $imgSize = $this->parentImg->getSize();
        $newLayerGD = imagecreatetruecolor($imgSize['w'], $imgSize['h']);
        imagealphablending($newLayerGD, false);
        imagefill($newLayerGD, 0, 0, 0x7F000000);
        imagecopy(
            $newLayerGD, $this->gdImage, 
            $this->offsetX, $this->offsetY, 
            0, 0, 
            imagesx($this->gdImage), imagesy($this->gdImage)
        ); 
        imagedestroy($this->gdImage);
        $this->gdImage = $newLayerGD;
        $this->offsetX = $this->offsetY = 0;
        $this->sizeX = $imgSize['w'];
        $this->sizeY = $imgSize['h'];
        $this->paint->attachToLayer($this);
        $this->filter->attachToLayer($this);
    }
    
    public function setParentImg($parentImg) {
        $this->parentImg = $parentImg;
        $this->transformPermanently();
    }
    
    
    public function setRenderer($rend) {
        if($rend instanceof Renderers\ILayerRenderer) {
            $rend->attachLayer($this);
            $this->renderer = $rend;
        }
    }
    
    // CREATE
    
    public static function createFromGD($gdResource) {
        if(Image::isValidGDImage($gdResource)) {
            $l = new Layer($gdResource);
            return $l;
        }
    }
    public static function createFromFile($fileName) {
        if(is_string($fileName) && file_exists($fileName)) {
            $gdResource = imagecreatefromstring(file_get_contents($fileName));
            return self::createFromGD($gdResource);
        }
    }

}