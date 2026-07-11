<?php

/**
 * =========================================================
 * app/Core/Validator.php — Clase de validación centralizada
 *
 * Valida datos de entrada y acumula errores:
 * · Comprueba campos obligatorios y formatos
 * · Valida longitud mínima y máxima
 * · Verifica coincidencia de campos
 * =========================================================
 */

namespace App\Core;

class Validator
{
    private array $errors = [];
    private array $data;

    /**
     * @param array $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param string $field
     * @param string|null $message
     * @return self
     */
    public function required(string $field, ?string $message = null): self
    {
        if (empty($this->data[$field])) {
            $this->errors[$field] = $message ?: "El campo $field es obligatorio.";
        }
        return $this;
    }

    /**
     * @param string $field
     * @param string|null $message
     * @return self
     */
    public function email(string $field, ?string $message = null): self
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?: "El campo $field debe ser un email válido.";
        }
        return $this;
    }

    /**
     * @param string $field
     * @param int $length
     * @param string|null $message
     * @return self
     */
    public function minLength(string $field, int $length, ?string $message = null): self
    {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?: "El campo $field debe tener al menos $length caracteres.";
        }
        return $this;
    }

    /**
     * @param string $field
     * @param int $length
     * @param string|null $message
     * @return self
     */
    public function maxLength(string $field, int $length, ?string $message = null): self
    {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?: "El campo $field no puede tener más de $length caracteres.";
        }
        return $this;
    }

    /**
     * @param string $field
     * @param string $otherField
     * @param string|null $message
     * @return self
     */
    public function matches(string $field, string $otherField, ?string $message = null): self
    {
        if (($this->data[$field] ?? null) !== ($this->data[$otherField] ?? null)) {
            $this->errors[$field] = $message ?: "El campo $field no coincide con $otherField.";
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        $first = reset($this->errors);
        return is_string($first) ? $first : null;
    }
}
