<?php
namespace Naomai\PHPLayers\Helpers;

class ImageExporter {
    protected \GdImage $gdSource;
    public function __construct(\GdImage $image) {
        $this->gdSource = $image;
    }

    public function asFile(string $fileName, string $format="png", int $quality=-1) {
        $binary = $this->asBinaryData(
            format: $format,
            quality: $quality
        );
        file_put_contents($fileName, $binary);
    }

    public function asDataUrl(string $format="png", int $quality=-1) {
        $binary = $this->asBinaryData(
            format: $format,
            quality: $quality
        );
        $binaryEncoded=base64_encode($binary);
        $mime = static::getMimeType($format);
        return "data:".$mime.";base64,".$binaryEncoded;
    }

    public function asBinaryData(string $format="png", int $quality=-1) : string {
        $mime = static::getMimeType($format);

        $binary = "";
        $image = $this->gdSource;

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

    static function getMimeType(string $format) {
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