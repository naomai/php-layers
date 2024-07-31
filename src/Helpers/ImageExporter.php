<?php
namespace Naomai\PHPLayers\Helpers;

class ImageExporter {
    protected \GdImage $gdSource;
    public function __construct(\GdImage $image) {
        $this->gdSource = $image;
    }

    public function toBrowser(string $format="png", int $quality=-1) : void {
        $mimeType = static::getMimeType($format);
        header("Content-Type: ".$mimeType);
        $binary = $this->asBinaryData(
            format: $format,
            quality: $quality
        );
        echo $binary;
    }

    public function asFile(string $fileName, string $format="png", int $quality=-1) : void {
        $binary = $this->asBinaryData(
            format: $format,
            quality: $quality
        );
        file_put_contents($fileName, $binary);
    }

    public function asDataUrl(string $format="png", int $quality=-1) : string {
        $binary = $this->asBinaryData(
            format: $format,
            quality: $quality
        );
        $mime = static::getMimeType($format);
        return static::encodeDataUrl(
            binary: $binary, 
            mimeType: $mime
        );
    }

    public function asBinaryData(string $format="png", int $quality=-1) : string {
        $image = $this->gdSource;
        $binary = static::getImageBinaryData(
            image: $image, 
            format: $format, 
            quality: $quality
        );
        return $binary;
    }

    static public function getImageBinaryData(
        \GdImage $image, 
        string $format="png", int $quality=-1
    ) : string {
        $mime = static::getMimeType($format);

        $binary = "";
        ob_start();
        switch($mime){
            case "image/gif":
                imagegif(image: $image);
                break;
            case "image/jpeg":
                imagejpeg(
                    image: $image, 
                    quality: $quality
                );
                break;
            case "image/png":
                imagepng(
                    image: $image,
                    quality: $quality
                );
                break;
            case "image/webp":
                imagewebp(
                    image: $image,
                    quality: $quality
                );
                break;

            case "image/avif":
                imageavif(
                    image: $image,
                    quality: $quality  
                );
                break;
            default:
                ob_get_clean();
                throw new \InvalidArgumentException("Invalid image format \"{$format}\".");
        }
        $binary = ob_get_clean();
        return $binary;
    }

    static public function encodeDataUrl(string $binary, string $mimeType) : string {
        $binaryEncoded=base64_encode($binary);
        return "data:".$mimeType.";base64,".$binaryEncoded;
    }

    static public function getMimeType(string $format) : string {
        $formatLC = strtolower($format);
        switch($formatLC){
            case "gif":
                return "image/gif";
            case "jpg":
            case "jpeg":
                return "image/jpeg";
            case "png":
                return "image/png";
            case "webp":
                return "image/webp";
            case "avif":
                return "image/avif";
            default:
                return "application/octet-stream";
        }
    }
}