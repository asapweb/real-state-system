<?php

namespace App\Casts;

use App\Enums\VoucherStatus;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class VoucherStatusCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?VoucherStatus
    {
        if ($value === null) {
            return null;
        }

        $normalized = $value === 'canceled' ? VoucherStatus::Cancelled->value : $value;

        return VoucherStatus::tryFrom($normalized);
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value instanceof VoucherStatus) {
            return $value->value;
        }

        if (is_string($value)) {
            $normalized = $value === 'canceled' ? VoucherStatus::Cancelled->value : $value;
            $enum = VoucherStatus::tryFrom($normalized);

            if (!$enum) {
                throw new InvalidArgumentException("Invalid voucher status [{$value}]");
            }

            return $enum->value;
        }

        if ($value === null) {
            return null;
        }

        throw new InvalidArgumentException('Invalid value provided for VoucherStatus cast.');
    }
}

