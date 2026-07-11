<?php

/**
 * =========================================================
 * app\Core\BusinessFormHandler.php — Manejador de formulario de comercio
 *
 * Procesa y valida los datos de comercios para admin y business:
 * · Sanitiza los campos del formulario
 * · Valida datos requeridos y formatos
 * · Devuelve datos limpios o lanza excepción con errores
 * =========================================================
 */

namespace App\Core;

class BusinessFormHandler
{
    /**
     * Procesa, sanitiza y valida los datos de comercios (Admin y Business)
     *
     * @param array $post Datos enviados desde el formulario
     * @return array Datos sanitizados y validados
     * @throws \InvalidArgumentException Si hay errores de validación
     */
    public static function process(array $post): array
    {
        $errors = [];

        $data = [
            'nombre' => trim($post['nombre'] ?? ''),
            'descripcion' => trim($post['descripcion'] ?? ''),
            'telefono' => trim($post['telefono'] ?? ''),
            'email' => filter_var(trim($post['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'web' => filter_var(trim($post['web'] ?? ''), FILTER_SANITIZE_URL),
            'user_id' => filter_var($post['user_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            'activo' => isset($post['activo']) ? 1 : 0,
            'categoria_id' => filter_var($post['categoria_id'] ?? 0, FILTER_VALIDATE_INT),
            'calle' => trim($post['calle'] ?? ''),
            'numero' => trim($post['numero'] ?? ''),
            'codigo_postal' => trim($post['codigo_postal'] ?? ''),
            'ciudad' => trim($post['ciudad'] ?? ''),
            'provincia' => trim($post['provincia'] ?? ''),
        ];

        if (empty($data['nombre'])) {
            $errors['nombre'] = "El nombre del comercio es obligatorio.";
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "El correo electrónico no tiene un formato válido.";
        }

        if ($data['categoria_id'] <= 0) {
            $errors['categoria_id'] = "Debes seleccionar una categoría obligatoriamente.";
        }

        if (empty($data['calle'])) {
            $errors['calle'] = "La calle es obligatoria.";
        }

        if (empty($data['ciudad'])) {
            $errors['ciudad'] = "La ciudad es obligatoria.";
        }

        if (!empty($errors)) {
            Session::setFlash('form_errors', $errors);
            throw new \InvalidArgumentException("Errores de validación en el formulario.");
        }

        return $data;
    }
}
