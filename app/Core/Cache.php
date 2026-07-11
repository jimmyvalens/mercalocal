<?php

/**
 * =========================================================
 * app/Core/Cache.php — Clase de caché
 *
 * Provee almacenamiento en Redis con respaldo en memoria local:
 * · Inicializa la conexión Redis si está disponible
 * · Recupera, guarda y elimina entradas de caché
 * · Borra toda la caché en Redis o en el fallback de array
 * =========================================================
 */

namespace App\Core;

use Predis\Client;

class Cache
{
    private static ?Client $redis = null;
    private static array $fallback = [];

    /**
     * Inicializa el cliente Redis.
     *
     * @return void
     */
    public static function init()
    {
        try {
            self::$redis = new Client();
            self::$redis->connect();
        } catch (\Exception) {
            self::$redis = null;
        }
    }

    /**
     * Obtiene un valor de caché.
     *
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        if (self::$redis) {
            $value = self::$redis->get($key);
            return $value ? unserialize($value) : null;
        }
        return self::$fallback[$key] ?? null;
    }

    /**
     * Guarda un valor en caché.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return void
     */
    public static function set($key, $value, $ttl = 3600)
    {
        if (self::$redis) {
            self::$redis->setex($key, $ttl, serialize($value));
        } else {
            self::$fallback[$key] = $value;
        }
    }

    /**
     * Elimina una entrada de caché.
     *
     * @param string $key
     * @return void
     */
    public static function delete($key)
    {
        if (self::$redis) {
            self::$redis->del([$key]);
        } else {
            unset(self::$fallback[$key]);
        }
    }

    /**
     * Limpia toda la caché.
     *
     * @return void
     */
    public static function clear()
    {
        if (self::$redis) {
            self::$redis->flushdb();
        } else {
            self::$fallback = [];
        }
    }
}

// Inicializar caché
Cache::init();
