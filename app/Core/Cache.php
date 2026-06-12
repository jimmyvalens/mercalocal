<?php
// app/Core/Cache.php
// Clase para caché usando Redis o fallback a array
namespace App\Core;

use Predis\Client;

class Cache
{
    private static $redis = null;
    private static $fallback = [];

    public static function init()
    {
        try {
            self::$redis = new Client();
            self::$redis->connect();
        } catch (\Exception $e) {
            // Fallback a array si Redis no está disponible
            self::$redis = null;
        }
    }

    public static function get($key)
    {
        if (self::$redis) {
            $value = self::$redis->get($key);
            return $value ? unserialize($value) : null;
        }
        return self::$fallback[$key] ?? null;
    }

    public static function set($key, $value, $ttl = 3600)
    {
        if (self::$redis) {
            self::$redis->setex($key, $ttl, serialize($value));
        } else {
            self::$fallback[$key] = $value;
        }
    }

    public static function delete($key)
    {
        if (self::$redis) {
            self::$redis->del([$key]);
        } else {
            unset(self::$fallback[$key]);
        }
    }

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
