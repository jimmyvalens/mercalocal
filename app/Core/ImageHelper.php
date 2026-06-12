<?php
// app/Core/ImageHelper.php
// Clase para manejo y compresión de imágenes
namespace App\Core;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageHelper
{
    private static $manager;

    public static function getManager()
    {
        if (!self::$manager) {
            self::$manager = new ImageManager(new Driver());
        }
        return self::$manager;
    }

    /**
     * Comprimir imagen manteniendo calidad
     */
    public static function compress($imagePath, $outputPath = null, $quality = 80, $maxWidth = 1200, $maxHeight = 1200)
    {
        if ($outputPath === null) {
            $outputPath = $imagePath;
        }

        $image = self::getManager()->read($imagePath);

        // Redimensionar si es necesario
        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            $image->scale(width: $maxWidth, height: $maxHeight);
        }

        // Comprimir y guardar
        $image->toJpeg($quality)->save($outputPath);

        return $outputPath;
    }

    /**
     * Crear thumbnail
     */
    public static function thumbnail($imagePath, $outputPath, $width = 300, $height = 300)
    {
        $image = self::getManager()->read($imagePath);
        $image->cover($width, $height);
        $image->toJpeg(80)->save($outputPath);
        return $outputPath;
    }

    /**
     * Validar tipo de imagen
     */
    public static function isValidImage($filePath)
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime = mime_content_type($filePath);
        return in_array($mime, $allowedTypes);
    }
}
