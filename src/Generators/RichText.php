<?php

    namespace Naomai\PHPLayers\Generators;

    require_once __DIR__ .  "/../Utils/FontCache.php";

    class RichText implements ILayerGenerator {
        protected $layer;
        
        protected $document = [];
        protected $currentParagraph = null;
        //viewport
        public $position = ["auto"=>true];
        public $margin = ["auto"=>true];
        //document properties
        public $backgroundColor = 0xFFFFFF;
        //current paragraph properties
        public $align = RichText::GDWRT_ALIGN_LEFT;
        //next-character properties
        public $textColor       = 0x000000;
        public $highlightColor  = 0x7FFFFFFF;
        public $font = "Lato";
        public $fontSize = 12;
        public $fontBold      = false;
        public $fontItalic    = false;
        public $textUnderline = false;
        public $textStrike    = false;
        
        //font sources
        protected $systemFonts;
        protected $wwwFonts;
        protected $defaultFont;

        protected FontSizer $sizer;
        
        
        const GDWRT_ALIGN_LEFT   = 0;
        const GDWRT_ALIGN_CENTER = 1;
        const GDWRT_ALIGN_RIGHT  = 2;
        
        
        public function __construct() {
            $this->wwwFonts = new \Naomai\FontCache();
            $this->wwwFonts->fontDir = __DIR__ ."/../Fonts";
            $this->wwwFonts->cacheFile = __DIR__ . "/WWWFonts.dat";
            if(file_exists($this->wwwFonts->cacheFile)) {
                $this->wwwFonts->preload();
            }else{
                $this->wwwFonts->scanFonts();
            }
            $this->defaultFont = $this->wwwFonts->getFontFamily("Lato");

            $this->sizer = new FontSizer;
        }
        
        public function getSize() {
            if(isset($this->position['auto']) && $this->position['auto']===true) {
                $layerRect = $this->layer->getLayerDimensions();
                return [
                    'x'=>0,'y'=>0,
                    'width'=>$layerRect['w'], 'height'=>$layerRect['h'],
                    'auto'=>true
                ];
            }else{
                return $this->position;
            }
        }
        public function getMargin() {
            $size = $this->getSize();
            if(isset($this->margin['auto'])) {
                
                $margin = ['left'=>16,'right'=>16,'top'=>16,'bottom'=>16];
            }else{
                $margin = $this->margin;
            }
            
            return $margin;
        }
        public function getInnerSize() {
            // with margins
            $size = $this->getSize();
            $margin = $this->getMargin();

            $size['x']     += $margin['left'];
            $size['width'] -= $margin['left']+$margin['right'];
            $size['y']     += $margin['top'];
            if($size['height']!='auto') {
                $size['height'] -= $margin['top'] + $margin['bottom'];
            }
            return $size;
        }

        
        public function write($string) {
            if($this->currentParagraph == null) {
                $this->newParagraph();
            }
            $newNode = new TextNode($this->currentParagraph);
            $newNode->textContent    = $string;
            $newNode->textColor      = $this->textColor;
            $newNode->highlightColor = $this->highlightColor;
            $newNode->font           = $this->font;
            $newNode->fontSize       = $this->fontSize;
            $newNode->fontBold       = $this->fontBold;
            $newNode->fontItalic     = $this->fontItalic;
            $newNode->textUnderline  = $this->textUnderline;
            $newNode->textStrike     = $this->textStrike;
            $newNode->setSizer($this->sizer);
            
            $this->currentParagraph->addNode($newNode);


            return $newNode;
        }
        public function insertNodeOfType($type) {
            if(is_subclass_of($type, '\Naomai\PHPLayers\Renderers\Node')) {
                if($this->currentParagraph == null) {
                    $this->newParagraph();
                }
                $newNode = new $type($this->currentParagraph);
                $this->currentParagraph->addNode($newNode);
                return $newNode;
            }
        }
        
        public function newParagraph() {
            if($this->currentParagraph != null) {
                $this->currentParagraph->align = $this->align;
                $this->document[] = $this->currentParagraph;
            }

            $newPar = new Paragraph($this);
            $this->currentParagraph = $newPar;
            return $newPar;
        }
        
        public function getFontFile($fontFamily, $type) {
            $ff = $this->wwwFonts->getFontFamily($fontFamily);
            if(count($ff) == 0) {
                $ff = $this->systemFonts->getFontFamily($fontFamily);
            }
            if(count($ff) == 0) {
                $ff = $this->defaultFont;
            }
            $typeLC = strtolower($type);
            
            if(isset($ff[$typeLC])) {
                $fontInfo = $ff[$typeLC];
            } elseif(isset($ff["regular"])) {
                $fontInfo = $ff["regular"];
            } else {
                $fontInfo = reset($ff);
            }
            return $fontInfo['path'];
        }
        
        public function attachLayer($layerObj) {
            $this->layer = $layerObj;
        }
        public function apply() {
            if($this->currentParagraph != null) {
                $this->currentParagraph->align = $this->align;
                $this->document[] = $this->currentParagraph;
            }
            
            $gdItems = [];
            
            $posOuter = $this->getSize();
            $pos = $this->getInnerSize();
            
            $offsetX = $pos['x'];
            $offsetY = $pos['y'];
            $docCalculatedHeight = 0;
            $y = 0;
            $toDraw = [];
            foreach($this->document as $item) {
                $newGD = $item->render();
                $docCalculatedHeight += imagesy($newGD);
                $toDraw[] = ['item'=>$item,'gd'=>$newGD];
            }
            
            
            
            if((isset($posOuter['auto']) && $posOuter['auto']==true) 
                || $posOuter['height']=='auto'
            ) {
                $margin = $this->getMargin();
            
                $docHeight = $docCalculatedHeight + $margin['bottom'];
                
            }else{
                $docHeight = $posOuter['height'];
            }
            //$this->layer->fill($this->backgroundColor);
            $painter = $this->layer->paint()->with(alphaBlend: true);
            $painter->rectangle(
                $posOuter['x'], $posOuter['y'],
                $posOuter['x']+$posOuter['width'], $posOuter['y']+$docHeight,
                GDRECT_FILLED,
                GDCOLOR_DEFAULT, 
                $this->backgroundColor
            );
            $docGD = $this->layer->getGDHandle();
            
            foreach($toDraw as $itemDef) {
                $itemDef['item']->documentPosY = $y;
                imagecopy(
                    $docGD, $itemDef['gd'],
                    $offsetX, $y+$offsetY,
                    0, 0,
                    imagesx($itemDef['gd']), imagesy($itemDef['gd'])
                );
                $itemDef['item']->notifyRendered();
                $y += imagesy($itemDef['gd']);
            }
            
        }
    }

    class FontSizer {
        protected $cachedMetrics = [];

        public function getCharacterMetrics(string $chr, string $fontFile, int $size) {
            $fontMetrics = $this->getFontCachedMetrics($fontFile, $size);
            
            if(!isset($fontMetrics[$chr])) {
                $fontMetrics[$chr] = $this->measureCharacter($chr, $fontFile, $size);
            }
            return $fontMetrics[$chr];
        }

        protected function getFontCachedMetrics(string $fontFile, int $size) : array {
            $fontIndex = self::getCacheIndexName($fontFile, $size);
            if(!isset($this->cachedMetrics[$fontIndex])) {
                $this->cachedMetrics[$fontIndex] = [];
            }
            return $this->cachedMetrics[$fontIndex];
        }

        protected function measureCharacter(string $chr, string $fontFile, int $size) : array {
            $fontIndex = self::getCacheIndexName($fontFile, $size);
            $gdBox = imagettfbbox($size, 0, $fontFile, $chr);
            $this->cachedMetrics[$fontIndex][$chr] = $gdBox;
            return $gdBox;
        }

        static protected function getCacheIndexName(string $fontFile, int $size) : string {
            return abs(crc32($fontFile))."_".$size;
        }
    }

    abstract class Node{
        protected RichText|Node $parentNode;
        protected $document;
        /* render() returns an array with following structure:
        ( 
            'gd'=>GD handle with rendered element,
            'rect'=>array of rects around each symbol
        )*/
        public function __construct(RichText|Node $parent) {
            $this->parentNode = $parent;
            $this->document = $this->getDocument();
        }
        protected function getDocument() {
            if($this->parentNode instanceof RichText) {
                return $this->parentNode;
            }else if($this->parentNode instanceof Node) {
                return $this->parentNode->getDocument();
            }else{
                die("WHA?");
            }
        }
        
        public abstract function render();
        public function notifyRenderResult($rect) {
            
        }
        
    }

    class TextNode extends Node {
        public $textContent = "";
        public $textColor;
        public $highlightColor;
        public $font;
        public $fontSize;
        public $fontBold;
        public $fontItalic;
        public $textUnderline;
        public $textStrike;
        protected FontSizer $sizer;
        
        public function render() {
            $fontFile = $this->document->getFontFile($this->font, $this->getFontType());
            
            
            // prepare boxes
            $rect = [];
            $w = 0;
            $h = 0;
            
            $offsetX = 0;
            $offsetY = 0;
            
            $minX=0; $minY=0; $maxX=0; $maxY=0;
            
            
            for($i=0; $i<mb_strlen($this->textContent); $i++) {
                $charRect = [];
                $char = mb_substr($this->textContent, $i, 1);
                if($char == " ") {
                    $charRect['white'] = true;
                } elseif($char == "\r") {
                    
                } elseif($char == "\n") {
                    $charRect['linefeed'] = true;
                } elseif($char == "\t") {
                    $charRect['tab'] = true;
                } else {
                    
                }
                $charRect['_char'] = $char;
                
                $gdBox = $this->sizer->getCharacterMetrics($char, $fontFile, $this->fontSize);
                
                $charRect['x'] = $w;
                $charRect['y'] = $gdBox[7];
                $charRect['width'] 
                    = max($gdBox[0], $gdBox[2], $gdBox[4], $gdBox[6]) 
                    - min($gdBox[0], $gdBox[2], $gdBox[4], $gdBox[6])+1;
                $charRect['height'] = $gdBox[1] - $gdBox[7];
                
                $minY=min($minY, $gdBox[1], $gdBox[3], $gdBox[5], $gdBox[7]); 
                    // Y of point closest to the top edge
                $maxY=max($maxY, $gdBox[1], $gdBox[3], $gdBox[5], $gdBox[7]); 
                    // Y of point closest to the bottom edge
                
                $w += $charRect['width'];

                $rect[] = $charRect;
            }
            $offsetX = 0;
            $offsetY = -$minY;
            $h = $maxY - $minY;
            
            // draw
            $elGD = imagecreatetruecolor($w, $h);
            imagealphablending($elGD, false);
            imagefilledrectangle($elGD, 0, 0, $w, $h, $this->highlightColor);
            imagealphablending($elGD, true);
            
            foreach($rect as $rectId=>$current) {
                $current['y'] += $offsetY;
                $rect[$rectId]['y'] += $offsetY;
                /*imagerectangle(
                    $elGD,
                    $current['x'],$current['y'], 
                    $current['x']+$current['width'],$current['y']+$current['height'],
                    0xff0000
                ); */
                imagettftext(
                    $elGD, $this->fontSize, 0,
                    $current['x']+$offsetX, $offsetY,
                    $this->textColor, $fontFile,
                    $current['_char']
                );
                
                unset($rect[$rectId]['_char']);
            }
            imagesavealpha($elGD, true);
            return ['gd'=>$elGD, 'rect'=>$rect];
        }
        
        protected function getFontType() {
            if($this->fontBold && $this->fontItalic) {
                $type = "Bold Italic";
            } elseif($this->fontBold) {
                $type = "Bold";
            } elseif($this->fontItalic) {
                $type = "Italic";
            } else {
                $type = "Regular";
            }
            return $type;
        }

        public function setSizer(FontSizer $sizer) {
            $this->sizer = $sizer;
        }
    }

    class Paragraph extends Node {
        public $align = RichText::GDWRT_ALIGN_LEFT;
        public $lineHeight = 16;
        public $documentPosY = 0;
        protected $nodes = [];
        protected $resultRects = [];
        
        public function addNode(Node $node) {
            if($node instanceof Paragraph) {
                return;
            }
            $this->nodes[] = $node;
        }
        
        public function render() {
            $docSize = $this->document->getInnerSize();
            //$marginSize = $this->document->getMargin();
            
            // throw all rects into one array
            $flatRects = [];
            foreach($this->nodes as $nodeId=>$node) {
                $nodeRendered = $node->render();
                $gd = $nodeRendered['gd'];
                $rects = $nodeRendered['rect'];
                foreach($rects as $rectId=>$rect) {
                    $rect['gd'] = $gd;
                    $rect['nodeId'] = $nodeId;
                    $rect['rectId'] = $rectId;
                    $flatRects[] = $rect;
                }
            }
            
            // prepare
            $width = $docSize['width'];
            $height = 0;
            
            $lineInfo = [];
            $charsForCurrentLine = 0;
            $charsForCurrentWord = 0;
            $currentLineHeight = $this->lineHeight;
            $wordBeginning = 0;
            
            $x = 0; //$y = 0;
            $xSafe = 0;
            $justWrapped = false;
            
            for($i=0; $i<count($flatRects); $i++) {
                $rect = $flatRects[$i];
                        
                if($x + $rect['width'] > $width || isset($rect['linefeed'])) {
                    // line overflow or newline 
                    if(isset($rect['resizable']) && $rect['width']>$width) {
                        // image too big
                        $flatRects[$i]['resizeTo'] = [
                            'width'=>$width, 
                            'height'=>$rect['height'] * ($width/$rect['width'])
                        ];
                    } elseif(!isset($rect['linefeed']) && $justWrapped) {
                        // break long word
                        $charsForCurrentLine = $charsForCurrentWord;
                        $charsForCurrentWord = 0;
                        //ECHO "CFCW=$charsForCurrentLine;";
                        $xSafe = $x;
                        $i-=1;
                    } elseif(!isset($rect['linefeed'])) {
                        // go back to last 'safe' position (first character of current word)
                        $i = $wordBeginning-1;
                    }
                    
                    if(isset($rect['linefeed']) || $xSafe>0) {
                        // line feed or overflow with whitespace character
                        if(isset($rect['linefeed'])) {
                            $charsForCurrentLine += $charsForCurrentWord+1;
                            $xSafe = $x;
                        }
                        //$charsForCurrentLine+=1;
                        //eCHO "LF=$charsForCurrentLine;";
                        $lineInfo[] = ["chars"=>$charsForCurrentLine,"width"=>$xSafe,"height"=>$currentLineHeight];
                        $height += $currentLineHeight;
                        $currentLineHeight = $this->lineHeight;
                        $wordBeginning = $i;
                    }
                    
                    $charsForCurrentWord = 0;
                    $charsForCurrentLine = 0;
                    $x = 0;
                    $xSafe = 0;
                    //$y += $this->lineHeight;
                    $justWrapped = true;
                } elseif(isset($rect['white'])) {
                    $xSafe = $x;
                    $wordBeginning = $i+1;
                    $justWrapped = false;
                    $charsForCurrentLine += $charsForCurrentWord+1;
                    $charsForCurrentWord = 0;
                    $x += $rect['width'];
                } else {
                    $charsForCurrentWord++;
                    $x += $rect['width'];
                    $currentLineHeight = max($currentLineHeight, $rect['height']);
                }
            }

            $charsForCurrentLine += $charsForCurrentWord;
            $lineInfo[] = [
                "chars"=>$charsForCurrentLine,
                "width"=>$x,
                "height"=>$currentLineHeight
            ];
            $height += $currentLineHeight;

            $gd = imagecreatetruecolor($width, $height);
            imagealphablending($gd, false);
            imagefilledrectangle($gd, 0, 0, $width, $height, 0x7f000000);
            imagealphablending($gd, true);

            $lineOffset = 0;
            $lineOffsetY = 0;
            foreach($lineInfo as $lineNum=>$line) {
                //echo "LIN$lineNum=".$line['chars']."!";
                $lineAlignOffset = round(($width - $line['width']) * ($this->align / 2));
                //$lineOffsetY = $lineNum * $this->lineHeight;
                
                $x = $lineAlignOffset;
                for($c=0; $c<$line['chars']; $c++) {
                    $rect = $flatRects[$lineOffset + $c];
                    if(isset($rect['linefeed'])) {
                        continue;
                    }

                    if($rect['gd'] !== null) {
                        imagecopy(
                            $gd, $rect['gd'],
                            $x, $lineOffsetY+$rect['y'],
                            $rect['x'] ,$rect['y'],
                            $rect['width'], $rect['height']
                        );
                    }
                    if(!isset($this->resultRects[$rect['nodeId']])) {
                        $this->resultRects[$rect['nodeId']] = ['gdResult'=>$gd,'rect'=>[]];
                    }

                    $this->resultRects[$rect['nodeId']]['rect'][$rect['rectId']] = [
                        'x'=>$x,
                        'y'=>$lineOffsetY+$rect['y'],
                        'layerX'=>$x+$docSize['x'],
                        'layerY'=>$lineOffsetY+$rect['y']+$docSize['y'],
                        'width'=>$rect['width'],
                        'height'=>$rect['height']
                    ];
                    $x += $rect['width'];
                }
                $lineOffset += $line['chars'];
                $lineOffsetY += $line['height'];
            }
            
            
            
            return $gd;
        }
        
        public function notifyRendered() {
            //notify
            foreach($this->resultRects as $nodeId=>$rectInfo) {
                foreach($rectInfo['rect'] as $rectId=>$rect) {
                    $rectInfo['rect'][$rectId]['y']+=$this->documentPosY;
                    $rectInfo['rect'][$rectId]['layerY']+=$this->documentPosY;
                }
                $this->nodes[$nodeId]->notifyRenderResult($rectInfo);
            }
        }
    }

    class ImageNode extends Node{
        public ?\GdImage $gdContainer;
        public function render() {
            if($this->gdContainer!==false) {
                $rect = [
                    'gd'=>$this->gdContainer, 
                    'rect'=>[
                            [
                                'x'=>0,
                                'y'=>0,
                                'width'=>imagesx($this->gdContainer),
                                'height'=>imagesy($this->gdContainer)
                            ]
                        ]
                    ];
                return $rect;
            } else {
                return ['gd'=>null,'rect'=>[]];
            }
        }
    }

