<?php

/**
 * =========================================================
 * app/Core/FileUploader.php — Utilidad para subida segura
 *
 * Gestiona la carga de imágenes con validaciones y rutas relativas:
 * · Valida tamaño y MIME de imágenes.
 * · Genera nombres seguros y guarda en el directorio de uploads.
 * · Devuelve rutas relativas preparadas para la aplicación.
 * =========================================================
 */

namespace App\Core;

class FileUploader
{
    private array $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    private int $maxSize = 5 * 1024 * 1024; // 5 MB
    private string $uploadDir;

    /**
     * FileUploader constructor.
     *
     * @param string|null $uploadDir Ruta absoluta al directorio de subida.
     */
    public function __construct($uploadDir = null)
    {
        $this->uploadDir = $uploadDir ?: ROOT_DIR . '/public/uploads';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Sube un archivo de forma segura.
     *
     * @param array $file El array $_FILES['input_name'].
     * @param string $prefix Prefijo para el archivo guardado.
     * @return string|false La ruta relativa del archivo subido o false si falla.
     */
    public function upload($file, $prefix = 'img_')
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        if ($file['size'] > $this->maxSize) {
            throw new \Exception('El archivo excede el tamaño máximo permitido (5MB).');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $this->allowedMimes)) {
            throw new \Exception('Formato de archivo no permitido. Solo se aceptan JPG, PNG y WEBP.');
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid($prefix) . '.' . strtolower($ext);

        $targetPath = rtrim($this->uploadDir, '/') . '/' . $filename;

        $publicPos = strpos($targetPath, 'public');
        if ($publicPos !== false) {
            $relativePath = substr($targetPath, $publicPos + 7);
        } else {
            $relativePath = 'uploads/' . ltrim(str_replace(ROOT_DIR . '/public/uploads', '', $targetPath), '/');
        }

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return str_replace('\\', '/', $relativePath);
        }

        return false;
    }

    /**
     * Procesa logo y hero de un comercio en una sola operación.
     *
     * @param array $files El array global $_FILES.
     * @param string|null $currentLogo Ruta actual del logo guardada en BD.
     * @param string|null $currentHero Ruta actual del hero guardada en BD.
     * @return array Rutas definitivas listas para impactar en la BD.
     */
    public function uploadBusinessImages(array $files, ?string $currentLogo = null, ?string $currentHero = null): array
    {
        $logoPath = $currentLogo;
        $heroPath = $currentHero;

        if (isset($files['logo']) && !empty($files['logo']['tmp_name'])) {
            $uploadedLogo = $this->upload($files['logo'], 'logo_');
            if ($uploadedLogo !== false) {
                $logoPath = $uploadedLogo;
            }
        }

        if (isset($files['hero']) && !empty($files['hero']['tmp_name'])) {
            $uploadedHero = $this->upload($files['hero'], 'hero_');
            if ($uploadedHero !== false) {
                $heroPath = $uploadedHero;
            }
        }

        return [
            'logo_path' => $logoPath,
            'hero_path' => $heroPath
        ];
    }
}
