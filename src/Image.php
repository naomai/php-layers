<?php
/** PHP-Layers
 * 
 * 2013 naomai
 * 
 * @version 0.2.1
 * 
 */

namespace Naomai\PHPLayers; 

define("GDIMAGE_SUPPORTS_AFFINE", function_exists("imageaffine"));

const IMAGE_RIGHT = -1;
const IMAGE_BOTTOM = -1;
    
class Image {
    protected $layers = []; // stack order (push-pop)
    protected $sizeX;
    protected $sizeY;
    protected $layerIdCounter = 0;
    protected $composer;
    
    /**
     *  Creates new Image object.
     *  
     *  @param int 	$width 	  or width of a new image
     *  @param int 	$height   height of a new image
     *  @since 0.1.0
     */	
    public function __construct($width, $height, $createLayer = true) {
        if(is_numeric($width) && is_numeric($height)) {
            $this->setSize($width, $height);
            if($createLayer) {
                $bgLayer = new Layer($width, $height);
                $bgLayer->name = "Background";
                $this->addLayerTop($bgLayer);
            }
            
            $this->setComposer(new Composers\DefaultComposer());
        }
    }
    
    
    /**
     *  Puts a layer object to the top of image's layer set.
     *  
     *  Inserted layer is drawn over the existing image.
     *  
     *  @param [in] $layerObj Parameter_Description
     *  @return Unique layer ID
     *  @since 0.1.0
     */
    public function addLayerTop(Layer $layerObj) {
        $this->layers[$this->layerIdCounter] = $layerObj; 
        $layerObj->setParentImg($this);
        return $this->layerIdCounter++;
    }
    
    /**
     *  Puts a layer object to the bottom of image's layer set.
     *  
     *  Inserted layer is drawn behind the existing image.
     *  
     *  @param [in] $layerObj Parameter_Description
     *  @return Unique layer ID
     *  @since 0.1.0
     */
    public function addLayerBottom(Layer $layerObj) {
        $this->layers = [($this->layerIdCounter)=>$layerObj] + $this->layers; 
        $layerObj->setParentImg($this);
        return $this->layerIdCounter++;
    }
    
    /**
     *  Create new layer and put it on top of layer set. 
     *  
     *  @return Unique layer ID for the new layer
     *  @since 0.1.0
     */
    public function newLayer() {
        $newLayer = new Layer($this->sizeX, $this->sizeY);
        $newLayer->clear();
        $newLayer->name = "Layer ".count($this->layers);
        return $this->addLayerTop($newLayer);
    }
    
    /**
     *  Change the image's layer composer object.
     *  
     *  @since 0.1.0
     */
    public function setComposer($composerObj) {
        $composerObj->setImage($this);
        $this->composer = $composerObj;
    }
    
    /**
     *  Gets the size of image object
     *  
     *  @return Array containing two elements: 
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
    public function setSize($w,$h) {
        $this->sizeX=$w;
        $this->sizeY=$h;
    }
    
    /**
     *  Gets the Layer object from layer set using unique layer ID
     *  
     *  @param int $id Unique layer ID
     *  @return Layer object matching the ID provided, or FALSE if ID is invalid.
     *  @since 0.0.0
     */
    public function getLayerById($id) {
        return isset($this->layers[$id]) ? $this->layers[$id] : false;
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
    public function getMerged() {
        $this->composer->fillLayers($this->layers);
        $finalLayer = $this->composer->mergeAll();
        return $finalLayer;
    }
    
    /**
     *  Finalize image into GD2 image resource.
     *  
     *  Merges all layers in image layer set using current layer composer.
     *  The result is a GD2 image resource accessible by native PHP functions.
     *  The original layer set is left intact.
     *  
     *  @return GD2 resource containing merged content of image
     *  @since 0.1.0
     */
    public function getMergedGD() {
        return $this->getMerged()->getGDHandle();
    }

    /**
     * Output image into Data URL, as lossless PNG format
     * 
     * @return string URL containing resulting image
     */
    
    public function getDataUrlPNG() {
        ob_start();
        imagepng($this->getMergedGD());
        $imgd=base64_encode(ob_get_clean());
        return "data:image/png;base64,".$imgd;
    }

    /**
     * Output image into Data URL, as lossy JPEG
     * 
     * @return string URL containing resulting image
     */
    public function getDataUrlJPEG() {
        ob_start();
        imagejpeg($this->getMergedGD());
        $imgd=base64_encode(ob_get_clean());
        return "data:image/jpeg;base64,".$imgd;
    }
    
    /**
     * Wrap existing GD image into new PHP Layers Image object
     * 
     * @return \Naomai\PHPLayers\Image 
     */
    public static function createFromGD($gdResource) {
        if(Image::isValidGDImage($gdResource)) {
            $gdImg = new Image(imagesx($gdResource), imagesy($gdResource), false);
            $gdImg->addLayerTop(new Layer($gdResource));
            return $gdImg;
        }
    }

    /**
     * Import image file into new PHP Layers Image object
     * 
     * @return \Naomai\PHPLayers\Image 
     */
    public static function createFromFile($fileName) {
        if(is_string($fileName) && file_exists($fileName)) {
            $gdResource = imagecreatefromstring(file_get_contents($fileName));
            return self::createFromGD($gdResource);
        }
    }

    /**
     * Check if variable contains valid GD2 image handle
     * 
     *  @param object|resource $layerObj Parameter_Description
     *  @return bool TRUE if provided value is a valid GD2 handle
     *  @since 0.2.0
     */
    public static function isValidGDImage($image) {
        if(version_compare(PHP_VERSION, '8.0.0', '>=')) {
            return is_object($image) && ($image instanceof \GdImage);
        }else{
            return is_resource($image) && get_resource_type($image)=="gd";
        }
    }


}





function clamp_byte($v) {
    return min(max((int)$v, 0), 255);
}

function clamp_int($v, $min, $max) {
    return min(max((int)$v, $min), $max);
}




