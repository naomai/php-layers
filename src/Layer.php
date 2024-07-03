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
        if(count($args) !== 0) {
            throw new \BadFunctionCallException(
                "Layer::__construct with arguments is deprecated"
            );
        }

        $this->setLayerDimensions(0, 0, 1, 1);

        $this->gdImage = imagecreatetruecolor(1, 1);
            
        $this->filter = new Filters\PHPFilters($this);
        
        $this->paint = new PaintTools\DefaultTools($this);

    }
    
    public function __destruct() {
        imagedestroy($this->gdImage);
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
            'w'=>$this->sourceSizeX,
            'h'=>$this->sourceSizeY
        ];
    }

    public function setLayerDimensions(int $offsetX, int $offsetY, int $width, int $height) {
        $this->offsetX = $offsetX;
        $this->offsetY = $offsetY;
        $this->sourceSizeX = $width;
        $this->sourceSizeY = $height;
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
    /**
     * Select entire layer surface
     *
     * @return Selection Helper object for transforming selection
     */
    public function selectWhole() : Selection {
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
        return new Selection($this->gdImage, $x, $y, $w, $h);
    }
    
    public function pasteClip(Clip $clip, int $x=0, int $y=0) : void {
        $clipImg = $clip->getContents();
        imagecopy($this->gdImage, $clipImg, $x, $y, 0, 0, imagesx($clipImg), imagesy($clipImg));
    }
    
    public function transformPermanently() : void {
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
    
    public function setParentImg(Image $parentImg) : void {
        $this->parentImg = $parentImg;
    }
    
    
    public function setRenderer(Renderers\ILayerRenderer $rend) : void {
        $rend->attachLayer($this);
        $this->renderer = $rend;
    }

    public function importFromGD(\GdImage $gdSource) : Layer {
        $this->gdImage = $gdSource;
        $this->sizeX = imagesx($this->gdImage);
        $this->sizeY = imagesy($this->gdImage);
        $this->setLayerDimensions(0, 0, $this->sizeX, $this->sizeY);
        $this->transformPermanently();
        return $this;
    }

    public function importFromFile(string $fileName) : Layer {
        if(!file_exists($fileName)) {
            throw new \RuntimeException("File not found: ".$fileName);
        }
        $gdSource = imagecreatefromstring(file_get_contents($fileName));
        $this->importFromGD($gdSource);
        return $this;
    }


}