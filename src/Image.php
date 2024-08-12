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
    protected Composers\LayerComposerBase $composer;

    /**
     *  Create new image with given dimensions.
     *  
     *  @param int 	$width 	      width of a new image
     *  @param int 	$height       height of a new image
     *  @param bool $createLayer  if true, a background layer is automatically created. 
     *         This layer is fully transparent.
     *  @since 0.1.0
     */	
    public function __construct(int $width, int $height, bool $createLayer = true) {
        $this->layers = new LayerStack();
        $this->setSize($width, $height);
        if($createLayer) {
            $bgLayer = $this->newLayer("Background");
        }
        
        $this->setComposer(new Composers\DefaultComposer());
    }
    
    
    /**
     *  Puts a layer object to the top of image's Layer Stack.
     * 
     *  *Inserted layer is drawn over the existing image.*
     *  
     *  This method also *attaches* layer to the image.
     *  If the layer is already on the stack, it will be moved from 
     *  its previous place.
     *  
     *  @param Layer $layerObj  Layer to be put
     *  @return int New *Layer index* of the layer in Stack
     *  @since 0.1.0
     */
    public function layerPutTop(Layer $layerObj) : int {
        if($this->layers->getIndexOf($layerObj)===false) {
            $layerObj->setParentImg($this);
        }
        $index = $this->reorder($layerObj)->putTop();
        return $index;
    }
    
    /**
     *  Puts a layer object to the bottom of image's Layer Stack.
     *  
     *  *Inserted layer is drawn behind the existing image.*
     *  
     *  This method also *attaches* layer to the image.
     *  If the layer is already on the stack, it will be moved from its
     *  previous place.
     *  
     *  @param Layer $layerObj  Layer to be put
     *  @return int New *Layer index* of the layer in Stack
     *  @since 0.1.0
     */
    public function layerPutBottom(Layer $layerObj) : int {
        if($this->layers->getIndexOf($layerObj)===false) {
            $layerObj->setParentImg($this);
        }
        $index = $this->reorder($layerObj)->putBottom();
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
        $newLayer->setSurfaceDimensions($this->sizeX, $this->sizeY);
        $newLayer->setParentImg($this);
        $newLayer->clear();
        if(is_null($name)) {
            $name = "Layer ".$this->layers->getCount();
        }
        $newLayer->name = $name;
        $this->layerPutTop($newLayer);
        return $newLayer;
    }
    
    /**
     *  Change the image's layer composer object.
     *  
     *  @since 0.1.0
     */
    public function setComposer(Composers\LayerComposerBase $composerObj) : void {
        $composerObj->setImage($this);
        $this->composer = $composerObj;
    }

    /**
     *  Get the image's layer composer object.
     *  
     */
    public function getComposer() : Composers\LayerComposerBase {
        return $this->composer;
    }
    
    /**
     *  Gets the size of image object
     *  
     *  @return array containing two elements: 
     *          'w': image width, 'h': image height
     *  @since 0.1.0
     */
    public function getSize() : array {
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
    public function setSize(int $w, int $h) : void {
        if($w<=0 || $h<=0) {
            throw new \InvalidArgumentException("Invalid image size, dimensions should be > 0");
        }
        $this->sizeX=$w;
        $this->sizeY=$h;
    }
    
    /**
     *  Gets the Layer object from layer set using unique layer ID
     *  
     *  @param int $id Index of the layer in Layer Stack.
     *                 If negative, count from the last layer.
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
     *  The new layer **is not attached** to the image. This means
     *  you cannot use reordering functions on it.
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
     * @deprecated use export() instead
     * @param int $quality Quality of the result image
     * @return string URL containing resulting image
     */
    
    public function getDataUrlPNG(int $quality=-1) : string {

        return $this->export()->asDataUrl(
            format: "png",
            quality: $quality
        );
    }

    /**
     * Output image into Data URL, as lossy JPEG
     * 
     * @deprecated use export() instead
     * @param int $quality Quality of the result image
     * @return string URL containing resulting image
     */
    public function getDataUrlJPEG(int $quality=-1) : string {
        return $this->export()->asDataUrl(
            format: "jpeg",
            quality: $quality
        );
    }

    // method chaining ... methods

    public function export() : Helpers\ImageExporter {
        $gdResult = $this->getMergedGD();
        $exporter = new Helpers\ImageExporter($gdResult);
        return $exporter;
    }

    /**
     * Access helper object for changing layer order on stack.
     * 
     * @param Layer $layerToMove  a `Layer` to be relocated, 
     *        **must** be attached to the `Image`
     * @return Helpers\LayerReorderCall helper object providing 
     *         reordering methods. 
     */
    public function reorder(Layer $layerToMove) : Helpers\LayerReorderCall {
        //$layerIndex = $this->layers->getIndexOf($layerToMove);
        $layerParent = $layerToMove->getParentImg();
        if($layerParent !== $this) {
            throw new \RuntimeException("Layer is not attached to the target image.");
        }

        $reorderCall = new Helpers\LayerReorderCall($this->layers);
        $reorderCall->setLayerToMove($layerToMove);
        return $reorderCall;
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
        $gdHandle = @imagecreatefromstring(file_get_contents($fileName));
        if($gdHandle===false){
            throw new \RuntimeException("Invalid or malformed image file: ".$fileName);
        }
        return self::createFromGD($gdHandle);
    }

    /**
     * Access the LayerStack of the image
     * 
     * @return LayerStack object containing all the layers of image
     */
    public function getLayerStack() : LayerStack {
        return $this->layers;
    }

    /**
     * Get number of layers attached to the image
     * 
     * @return int number of layers
     */
    public function getLayerCount() : int {
        return $this->layers->getCount();
    }
}


