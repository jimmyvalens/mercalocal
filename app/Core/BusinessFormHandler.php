<?php

namespace App\Core;

class BusinessFormHandler
{
    /**
     * Procesa, sanitiza y valida los datos de comercios (Admin y Business)
     */
    public static function process(array $post): array
    {
        $errors = [];

        // 1. Sanitización exhaustiva
        $data = [
            'nombre'        => trim($post['nombre'] ?? ''),
            'descripcion'   => trim($post['descripcion'] ?? ''),
            'telefono'      => trim($post['telefono'] ?? ''),
            'email'         => filter_var(trim($post['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'web'           => filter_var(trim($post['web'] ?? ''), FILTER_SANITIZE_URL),
            'user_id'       => filter_var($post['user_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            'activo'        => isset($post['activo']) ? 1 : 0,
            'categoria_id'  => filter_var($post['categoria_id'] ?? 0, FILTER_VALIDATE_INT),
            'calle'         => trim($post['calle'] ?? ''),
            'numero'        => trim($post['numero'] ?? ''),
            'codigo_postal' => trim($post['codigo_postal'] ?? ''),
            'ciudad'        => trim($post['ciudad'] ?? ''),
            'provincia'     => trim($post['provincia'] ?? ''),
        ];

        // 2. Validación quirúrgica campo por campo
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

        // Si saltan errores, los inyectamos indexados por campo y lanzamos excepción
        if (!empty($errors)) {
            Session::setFlash('form_errors', $errors);
            throw new \InvalidArgumentException("Errores de validación en el formulario.");
        }

        return $data;
    }
}
