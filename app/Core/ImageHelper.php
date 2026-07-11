<?php

/**
 * =========================================================
 * app/Core/ImageHelper.php — Ayudante para manejo de imágenes
 *
 * Funciones para compresión, redimensionado y validación de imágenes:
 * · Inicializa o reutiliza el gestor de imagen
 * · Comprime imágenes manteniendo calidad y tamaño máximo
 * · Genera thumbnails y valida tipos MIME permitidos
 * =========================================================
 */

namespace App\Core;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageHelper
{
    private static ?ImageManager $manager = null;

    /**
     * Obtener el administrador de imágenes.
     *
     * @return ImageManager
     */
    public static function getManager(): ImageManager
    {
        if (!self::$manager) {
            self::$manager = new ImageManager(new Driver());
        }
        return self::$manager;
    }

    /**
     * Comprimir imagen manteniendo calidad.
     *
     * @param string $imagePath
     * @param string|null $outputPath
     * @param int $quality
     * @param int $maxWidth
     * @param int $maxHeight
     * @return string
     */
    public static function compress(string $imagePath, ?string $outputPath = null, int $quality = 80, int $maxWidth = 1200, int $maxHeight = 1200): string
    {
        if ($outputPath === null) {
            $outputPath = $imagePath;
        }

        $image = self::getManager()->read($imagePath);

        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            $image->scale(width: $maxWidth, height: $maxHeight);
        }

        $image->toJpeg($quality)->save($outputPath);

        return $outputPath;
    }

    /**
     * Crear thumbnail de la imagen.
     *
     * @param string $imagePath
     * @param string $outputPath
     * @param int $width
     * @param int $height
     * @return string
     */
    public static function thumbnail(string $imagePath, string $outputPath, int $width = 300, int $height = 300): string
    {
        $image = self::getManager()->read($imagePath);
        $image->cover($width, $height);
        $image->toJpeg(80)->save($outputPath);
        return $outputPath;
    }

    /**
     * Validar tipo de imagen.
     *
     * @param string $filePath
     * @return bool
     */
    public static function isValidImage(string $filePath): bool
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime = mime_content_type($filePath);
        return in_array($mime, $allowedTypes, true);
    }
}
