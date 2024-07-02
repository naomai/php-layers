<?php

namespace Naomai\PHPLayers; 

class Layer {
    protected $gdImage = null;
    
    /**
     *  @var int $offsetX Layer position on the destination image (X coordinate) 
     *  @var int $offsetY Layer position on the destination image (Y coordinate) 
     */
    public $offsetX=0;
    public $offsetY=0;
    public $name = "";
    protected $sizeX;
    protected $sizeY;
    protected $sourceSizeX;
    protected $sourceSizeY;
    protected $blending = Layer::GDLAYER_BLEND_NORMAL;
    protected $opacity = 100;
    /**
     *  @var GDLayerFilter	$filter 	Object providing image filters
     *  @var int 			$paint 		Object providing drawing functions
     *  @var int 			$renderer 	Image preprocessor used before merging with other layers
     */
    public $filter;
    public $paint;
    public $renderer;
    protected $parentImg;
    
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
    
    public function getLayerDimensions() {
        return  [
            'x'=>$this->offsetX,
            'y'=>$this->offsetY,
            'w'=>$this->sizeX,
            'h'=>$this->sizeY
        ];
    }
    public function getGDHandle() {
        return $this->gdImage;
    }
    
    public function setOpacity($opacity) {
        $this->opacity = $opacity;
    }
    public function getOpacity() {
        return $this->opacity;
    }
    
    /* PAINT */
    public function fill($color) {
        imagealphablending($this->gdImage, false);
        imagefilledrectangle($this->gdImage, 0, 0, $this->sizeX-1, $this->sizeY-1, $color);
        imagealphablending($this->gdImage, true);
    }

    public function clear() {
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