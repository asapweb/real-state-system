<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuration extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    /**
     * Obtener el valor de una configuración por grupo y clave.
     *
     * @param string $group
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $group, string $key, $default = null)
    {
        return Cache::remember("config_{$group}_{$key}", 3600, function () use ($group, $key, $default) {
            return self::where('group', $group)->where('key', $key)->first()->value ?? $default;
        });
    }

    /**
     * Obtener todas las configuraciones de un grupo.
     *
     * @param string $group
     * @return array
     */
    public static function getGroup(string $group): array
    {
        return Cache::remember("config_group_{$group}", 3600, function () use ($group) {
            return self::where('group', $group)->pluck('value', 'key')->toArray();
        });
    }
}
