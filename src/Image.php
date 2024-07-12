<?php
/** PHP-Layers
 * 
 * 2013 naomai
 * 
 * @version 0.2.1
 * 
 */

namespace Naomai\PHPLayers; 
    
class Image {
    protected LayerStack $layers; 
    protected $sizeX;
    protected $sizeY;
    protected Composers\DefaultComposer $composer;

    const IMAGE_RIGHT = -1;
    const IMAGE_BOTTOM = -1;
    
    /**
     *  Creates new Image object.
     *  
     *  @param int 	$width 	  width of a new image
     *  @param int 	$height   height of a new image
     *  @since 0.1.0
     */	
    public function __construct(int $width, int $height, $createLayer = true) {
        $this->layers = new LayerStack();
        $this->setSize($width, $height);
        if($createLayer) {
            $bgLayer = $this->newLayer("Background");
        }
        
        $this->setComposer(new Composers\DefaultComposer());
    }
    
    
    /**
     *  Puts a layer object to the top of image's layer set.
     *  
     *  Inserted layer is drawn over the existing image.
     *  
     *  @param Layer $layerObj layer to be put
     *  @return int Index of the layer in Layer Stack
     *  @since 0.1.0
     */
    public function layerPutTop(Layer $layerObj) {
        $index = $this->reorder($layerObj)->putTop();
        $layerObj->setParentImg($this);
        return $index;
    }
    
    /**
     *  Puts a layer object to the bottom of image's layer set.
     *  
     *  Inserted layer is drawn behind the existing image.
     *  
     *  @param Layer $layerObj layer to be put
     *  @return int Unique layer ID
     *  @since 0.1.0
     */
    public function layerPutBottom(Layer $layerObj) {
        $index = $this->reorder($layerObj)->putBottom();
        $layerObj->setParentImg($this);
        return $index;
    }
    
    /**
     *  Create new layer and put it on top of layer set. 
     *  
     *  @return Layer new layer object
     *  @since 0.1.0
     */
    public function newLayer(string $name=null) : Layer {
        $newLayer = new Layer();
        $newLayer->setParentImg($this);
        $newLayer->setSurfaceDimensions($this->sizeX, $this->sizeY);
        $newLayer->transformPermanently();
        $newLayer->clear();
        if(is_null($name)) {
            $name = "Layer ".$this->layers->getCount();
        }
        $newLayer->name = $name;
        $this->layerPutTop($newLayer);
        return $newLayer;
    }

    public function reorder(Layer $layerToMove) {
        $reorderCall = new Helpers\LayerReorderCall($this->layers);
        $reorderCall->setLayerToMove($layerToMove);
        return $reorderCall;
    }
    
    /**
     *  Change the image's layer composer object.
     *  
     *  @since 0.1.0
     */
    public function setComposer(Composers\DefaultComposer $composerObj) {
        $composerObj->setImage($this);
        $this->composer = $composerObj;
    }
    
    /**
     *  Gets the size of image object
     *  
     *  @return array containing two elements: 
     *          'w': image width, 'h': image height
     *  @since 0.1.0
     */
    public function getSize() {
        return [
            'w'=>$this->sizeX,
            'h'=>$this->sizeY
        ];
    }
    
    /**
     *  Sets the new side of image object.
     *  
     *  This method behaves like "crop" function - only manipulates
     *  the canvas size, without resizing the existing content.
     *  
     *  @param int $w New image width
     *  @param int $h New image height
     *  @since 0.1.0
     */
    public function setSize(int $w, int $h) {
        if($w<=0 || $h<=0) {
            throw new \InvalidArgumentException("Invalid image size, dimensions should be > 0");
        }
        $this->sizeX=$w;
        $this->sizeY=$h;
    }
    
    /**
     *  Gets the Layer object from layer set using unique layer ID
     *  
     *  @param int $id Index of the layer in Layer Stack
     *  @return ?Layer object matching the index provided, or null if invalid.
     */
    public function getLayerByIndex(int $id) : ?Layer {
        return $this->layers->getLayerByIndex($id);
    }
    
    /**
     *  Finalize image into Layer object.
     *  
     *  Merges all layers in image layer set using current layer composer.
     *  The result is a new Layer object. The original layer set is left intact.
     *  
     *  @return Layer object containing merged content of image
     *  @since 0.1.0
     */
    public function getMerged() : Layer {
        $this->composer->setLayerStack($this->layers);
        $finalLayer = $this->composer->mergeAll();
        return $finalLayer;
    }
    
    /**
     *  Finalize image into GD2 image handle.
     *  
     *  Merges all layers in image layer set using current layer composer.
     *  The result is a GD2 image handle accessible by native PHP functions.
     *  The original layer set is left intact.
     *  
     *  @return \GdImage GD2 handle containing merged content of image
     *  @since 0.1.0
     */
    public function getMergedGD() : \GdImage {
        return $this->getMerged()->getGDHandle();
    }

    /**
     * Output image into Data URL, as lossless PNG format
     * 
     * @return string URL containing resulting image
     */
    
    public function getDataUrlPNG() : string {
        $gdResult = $this->getMergedGD();
        ob_start();
        imagepng($gdResult);
        $imgd=base64_encode(ob_get_clean());
        return "data:image/png;base64,".$imgd;
    }

    /**
     * Output image into Data URL, as lossy JPEG
     * 
     * @return string URL containing resulting image
     */
    public function getDataUrlJPEG() : string {
        $gdResult = $this->getMergedGD();
        ob_start();
        imagejpeg($gdResult);
        $imgd=base64_encode(ob_get_clean());
        return "data:image/jpeg;base64,".$imgd;
    }
    
    /**
     * Wrap existing GD image into new PHP Layers Image object
     * 
     * @return Image 
     */
    public static function createFromGD(\GdImage $gdHandle) : Image {
        $imageObj = new Image(imagesx($gdHandle), imagesy($gdHandle), true);
        $bgLayer = $imageObj->getLayerByIndex(0);
        $bgLayer->importFromGD($gdHandle);
        return $imageObj;
    }

    /**
     * Import image file into new PHP Layers Image object
     * 
     * @return Image 
     */
    public static function createFromFile(string $fileName) : Image {
        if(!file_exists($fileName)) {
            throw new \RuntimeException("File not found: ".$fileName);
        }
        $gdHandle = imagecreatefromstring(file_get_contents($fileName));
        return self::createFromGD($gdHandle);
    }
}

function clamp_byte($v) {
    return min(max((int)$v, 0), 255);
}

function clamp_int($v, $min, $max) {
    return min(max((int)$v, $min), $max);
}
