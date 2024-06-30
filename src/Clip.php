<?php

namespace Naomai\PHPLayers; 

class Clip {
    protected $image;
    public function __construct($gdImage){
        $img = imagecreatetruecolor(imagesx($gdImage),imagesy($gdImage));
        imagecopy($img,$gdImage,0,0,0,0,imagesx($gdImage),imagesy($gdImage));
        $this->image = $img;
    }
    public function __destruct(){
        imagedestroy($this->image);	
    }
    public function getContents(){
        return $this->image;		
    }
}