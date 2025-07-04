<?php

use Carbon\Carbon;

if (! function_exists('normalizePeriod')) {
    /**
     * Normaliza un valor de período (ej. '2025-08') a un Carbon con día 1 del mes.
     *
     * @param string|Carbon|null $value
     * @return Carbon|null
     */
    function normalizePeriod(string|Carbon|null $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy()->startOfMonth();
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfMonth();
        }

        try {
            return Carbon::parse($value)->startOfMonth();
        } catch (\Exception) {
            return null;
        }
    }
}

if (! function_exists('normalizePeriodOrFail')) {
    /**
     * Normaliza un valor de período a un Carbon con día 1 del mes. Lanza excepción si no es válido.
     *
     * @param string|Carbon $value
     * @return Carbon
     *
     * @throws InvalidArgumentException
     */
    function normalizePeriodOrFail(string|Carbon $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->startOfMonth();
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfMonth();
        }

        try {
            return Carbon::parse($value)->startOfMonth();
        } catch (\Exception $e) {
            throw new InvalidArgumentException("El valor '{$value}' no es un período válido.");
        }
    }
}
