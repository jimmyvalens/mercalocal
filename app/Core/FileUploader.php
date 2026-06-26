<?php
// =========================================================
// app/Core/FileUploader.php — Utilidad para subida segura
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

    /**
     * 🔥 MÉTODO UNIFICADO PARA IMÁGENES DE COMERCIOS
     * Procesa de golpe Logo y Hero aprovechando las validaciones seguras de esta clase.
     * Soporta creación (parámetros null) y edición (pasando las rutas actuales de la BD).
     * * @param array $files El array global $_FILES
     * @param string|null $currentLogo Ruta actual del logo guardada en BD (para edición)
     * @param string|null $currentHero Ruta actual del hero guardada en BD (para edición)
     * @return array Con las rutas definitivas listas para impactar en la BD
     */
    public function uploadBusinessImages(array $files, ?string $currentLogo = null, ?string $currentHero = null): array
    {
        // Por defecto mantenemos lo que hay (si es creación será null, si es edición conservará la foto vieja)
        $logoPath = $currentLogo;
        $heroPath = $currentHero;

        // 1. Procesar el Logo (input name="logo")
        if (isset($files['logo']) && !empty($files['logo']['tmp_name'])) {
            $uploadedLogo = $this->upload($files['logo'], 'logo_');
            if ($uploadedLogo !== false) {
                $logoPath = $uploadedLogo; // Guardará algo como: "uploads/businesses/logo_64b2a.jpg"
            }
        }

        // 2. Procesar el Banner/Hero (input name="hero")
        if (isset($files['hero']) && !empty($files['hero']['tmp_name'])) {
            $uploadedHero = $this->upload($files['hero'], 'hero_');
            if ($uploadedHero !== false) {
                $heroPath = $uploadedHero; // Guardará algo como: "uploads/businesses/hero_c3b1a.webp"
            }
        }

        return [
            'logo_path' => $logoPath,
            'hero_path' => $heroPath
        ];
    }
}
