<?php
namespace Naomai\PHPLayers;

define('GDRECT_BORDER', 1);
define('GDRECT_FILLED', 2);
define('GDRECT_FILLEDBORDER', GDRECT_BORDER|GDRECT_FILLED);
define('GDALIGN_LEFT', 0);
define('GDALIGN_CENTER', 1);
define('GDALIGN_RIGHT', 2);

define('GDCOLOR_DEFAULT', -1);

class Painter {
    public $alphaBlend = false;
    public $antiAlias = false;
    public $lineColor = 0xFFFFFF;
    public $borderColor = 0xFF0000;
    public $lineSize = 1;

    protected $destLayer;
    protected $destGD;

    public function __construct(?Layer $layerObj=null) {
        if($layerObj !== null) {
            $this->attachToLayer($layerObj);
        }
    }
    
    public function attachToLayer($layerObj) {
        $this->destLayer = $layerObj;
        $this->destGD = $layerObj->getGDHandle();
    }

    public function attachToGD(\GdImage $gdResource) {
        $this->destGD = $gdResource;
    }

    public function setPaintOptions(...$options) {
        foreach($options as $prop=>$value) {
            if(!property_exists($this, $prop)) {
                throw new \InvalidArgumentException("Trying to set invalid paint option '{$prop}'");
            }
            $propReflection = new \ReflectionProperty(get_class($this), $prop);
            if(!$propReflection->isPublic()) {
                throw new \InvalidArgumentException("Trying to set invalid paint option '{$prop}'");
            }

            $this->{$prop} = $value;
        }
    }
        
    // PAINT FUNCTIONS
    public function pixel(int $x, int $y, $color=GDCOLOR_DEFAULT) {
        $this->setDrawingConfig();
        imagesetpixel($this->destGD, $x, $y, $this->getLineColor($color));
    }
    public function line(int $x1, int $y1, int $x2, int $y2, $color=GDCOLOR_DEFAULT) {
        $this->setDrawingConfig();
        
        imageline($this->destGD, $x1, $y1, $x2, $y2, $this->getLineColor($color));
    }

    public function rectangle(
        int $x1, int $y1, 
        int $x2, int $y2, 
        int $type=GDRECT_BORDER, 
        int $colorBorder=GDCOLOR_DEFAULT, int $colorFill=GDCOLOR_DEFAULT
    ) {
        $this->setDrawingConfig();
        if($type & GDRECT_FILLED) {
            $crop = 0; //ceil($this->lineSize/2);
            
            imagefilledrectangle(
                $this->destGD, 
                $x1+$crop, $y1+$crop, 
                $x2-$crop, $y2-$crop, 
                $this->getLineColor($colorFill)
            );
        }
        if($type & GDRECT_BORDER) {
            imagerectangle(
                $this->destGD, 
                $x1, $y1, 
                $x2, $y2, 
                $this->getBorderColor($colorBorder)
            );
        }
    }

    public function rectangleBox(
        array $box, 
        int $type=GDRECT_BORDER, int $colorBorder=GDCOLOR_DEFAULT, 
        int $colorFill=GDCOLOR_DEFAULT
    ) {
        $this->rectangle(
            $box['x'], $box['y'], 
            $box['x']+$box['w'], $box['y']+$box['h'], 
            $type, 
            $colorBorder, $colorFill
        );
    }

    public function polygon(
        array $verts, 
        int $type=GDRECT_BORDER, 
        int $colorBorder=GDCOLOR_DEFAULT, int $colorFill=GDCOLOR_DEFAULT
    ) {
        $this->setDrawingConfig();
        $gdVerts=[];
        foreach($verts as $v){
            $gdVerts[]=$v[0];
            $gdVerts[]=$v[1];
        }
        $gdVertsCount = count($verts);
        
        if($type & GDRECT_FILLED) {
            imagefilledpolygon(
                $this->destGD, 
                $gdVerts, $gdVertsCount, 
                $this->getLineColor($colorFill)
            );
        }
        if($type & GDRECT_BORDER) {
            imagepolygon(
                $this->destGD, 
                $gdVerts, $gdVertsCount, 
                $this->getBorderColor($colorBorder)
            );
        }
    }


    public function textBM(
        int $x, int $y, 
        string $text, 
        int $font=3, int $color=GDCOLOR_DEFAULT
    ) {
        $this->setDrawingConfig();
        imagestring($this->destGD, $font, $x, $y, $text, $this->getLineColor($color));
    }

    public function loadBMFont(string $fontFile) {
        return imageloadfont($fontFile);
    }

    public function textGetBox(int $x, int $y, string $text, array $params=[]) {
        $angle = isset($params['angle']) ? $params['angle'] : 0;
        $font = isset($params['font']) ? $params['font'] : __DIR__."/Fonts/Lato-Regular.ttf";
        $align = isset($params['align']) ? $params['align'] : GDALIGN_LEFT;
        $size = isset($params['size']) ? $params['size'] : 12;
        $box = imagettfbbox($size, $angle, $font, $text);
        $w = $box[2] - $box[0];
        $h = $box[1] - $box[7];
        $newX = $x - $box[6] - $w * $align / 2;
        $newY = $y - $box[7];
        return [
            'x'=>$x - $w*$align/2,
            'y'=>$y,
            'w'=>$w,
            'h'=>$h
        ];
    }

    public function text(int $x, int $y, string $text, array $params=[]) {
        $this->setDrawingConfig();
        $angle = isset($params['angle']) ? $params['angle'] : 0;
        $font = isset($params['font']) ? $params['font'] : __DIR__."/Fonts/Lato-Regular.ttf";
        $align = isset($params['align']) ? $params['align'] : GDALIGN_LEFT;
        $size = isset($params['size']) ? $params['size'] : 12;
        $color = isset($params['color']) ? $params['color'] : 0x808080;
        $box = imagettfbbox($size, $angle, $font, $text);
        $w = $box[2] - $box[0];
        $newX = round($x - $box[6] - $w * $align / 2);
        $newY = $y - $box[7];
        $this->setDrawingConfig();
        
        if(isset($params['shadow']) && $params['shadow']==true) {
            imagettftext($this->destGD, $size, $angle, $newX+1, $newY+1, 0x000000, $font, $text);
        }
        imagettftext(
            $this->destGD, 
            $size, $angle, 
            $newX, $newY, 
            $this->getLineColor($color), 
            $font, 
            $text
        );
        
    }

    // MISC
    protected function setDrawingConfig() {
        imagealphablending($this->destGD, $this->alphaBlend);
        imageantialias($this->destGD, $this->lineSize > 1 ? false : $this->antiAlias);
        imagesetthickness($this->destGD, $this->lineSize);
    }
    protected function getLineColor($color) {
        if($color===GDCOLOR_DEFAULT) {
            return $this->lineColor;
        }
        return $color;
    }
    protected function getBorderColor($color) {
        if($color===GDCOLOR_DEFAULT) {
            return $this->borderColor;
        }
        return $color;
    }
}