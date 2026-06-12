<?php
// app/Core/Validator.php
// Clase para validación centralizada de inputs
namespace App\Core;

class Validator
{
    private $errors = [];
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function required($field, $message = null)
    {
        if (empty($this->data[$field])) {
            $this->errors[$field] = $message ?: "El campo $field es obligatorio.";
        }
        return $this;
    }

    public function email($field, $message = null)
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?: "El campo $field debe ser un email válido.";
        }
        return $this;
    }

    public function minLength($field, $length, $message = null)
    {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?: "El campo $field debe tener al menos $length caracteres.";
        }
        return $this;
    }

    public function maxLength($field, $length, $message = null)
    {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?: "El campo $field no puede tener más de $length caracteres.";
        }
        return $this;
    }

    public function matches($field, $otherField, $message = null)
    {
        if ($this->data[$field] !== $this->data[$otherField]) {
            $this->errors[$field] = $message ?: "El campo $field no coincide con $otherField.";
        }
        return $this;
    }

    public function isValid()
    {
        return empty($this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getFirstError()
    {
        return reset($this->errors);
    }
}
