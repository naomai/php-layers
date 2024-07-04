<?php

namespace Naomai\PHPLayers; 

class Clip {
    protected \GdImage $image;

    public function __construct(\GdImage $gdImage) {
        $img = imagecreatetruecolor(imagesx($gdImage), imagesy($gdImage));
        imagecopy($img, $gdImage, 0, 0, 0, 0, imagesx($gdImage), imagesy($gdImage));
        $this->image = $img;
    }
    public function __destruct() {
        imagedestroy($this->image);	
    }


    public function getContents() : \GdImage {
        return $this->image;		
    }
}