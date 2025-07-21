<?php

namespace App\Enums;

enum IndexFrequency: string
{
    case DAILY = 'daily';
    case MONTHLY = 'monthly';

    public function getLabel(): string
    {
        return match($this) {
            self::DAILY => 'Diaria',
            self::MONTHLY => 'Mensual',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::DAILY => 'Valores de índice disponibles por fecha específica',
            self::MONTHLY => 'Valores de índice disponibles por mes',
        };
    }
}
