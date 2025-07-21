<?php

namespace App\Enums;

enum CalculationMode: string
{
    case PERCENTAGE = 'percentage';
    case RATIO = 'ratio';
    case MULTIPLICATIVE_CHAIN = 'multiplicative_chain';

    public function getLabel(): string
    {
        return match($this) {
            self::PERCENTAGE => 'Porcentaje',
            self::RATIO => 'Ratio',
            self::MULTIPLICATIVE_CHAIN => 'Cadena Multiplicativa',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::PERCENTAGE => 'Aplica directamente el porcentaje de variación del índice',
            self::RATIO => 'Calcula la variación entre dos valores del índice (estándar argentino)',
            self::MULTIPLICATIVE_CHAIN => 'Multiplica coeficientes mensuales entre sí desde el inicio del contrato',
        };
    }
}
