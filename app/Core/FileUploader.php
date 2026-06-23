<?php
// =========================================================
// src/Core/FileUploader.php — Utilidad para subida segura
// =========================================================
namespace App\Core;

class FileUploader
{
    private $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    private $maxSize = 5 * 1024 * 1024; // 5 MB
    private $uploadDir;

    public function __construct($uploadDir = null)
    {
        $this->uploadDir = $uploadDir ?: ROOT_DIR . '/public/uploads';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Sube un archivo de forma segura.
     * @param array $file El array $_FILES['input_name']
     * @param string $prefix Prefijo para el archivo guardado
     * @return string|false La ruta relativa del archivo subido o false si falla.
     */
    public function upload($file, $prefix = 'img_')
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Verificar tamaño
        if ($file['size'] > $this->maxSize) {
            throw new \Exception('El archivo excede el tamaño máximo permitido (5MB).');
        }

        // Verificar MIME type real (no confiar en la extensión ni en el tipo reportado por el navegador)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $this->allowedMimes)) {
            throw new \Exception('Formato de archivo no permitido. Solo se aceptan JPG, PNG y WEBP.');
        }

        // Generar nombre seguro
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid($prefix) . '.' . strtolower($ext);

        // La ruta absoluta donde se guardará el archivo
        $targetPath = rtrim($this->uploadDir, '/') . '/' . $filename;

        // Extraer la ruta relativa desde 'public/'
        $publicPos = strpos($targetPath, 'public');
        if ($publicPos !== false) {
            $relativePath = substr($targetPath, $publicPos + 7); // 7 es la longitud de 'public/'
        } else {
            $relativePath = 'uploads/' . ltrim(str_replace(ROOT_DIR . '/public/uploads', '', $targetPath), '/');
        }

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return str_replace('\\', '/', $relativePath); // Normalizar slashes
        }

        return false;
    }
}
