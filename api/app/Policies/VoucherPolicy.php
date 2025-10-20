<?php

namespace App\Policies;

use App\Enums\VoucherStatus;
use App\Models\User;
use App\Models\Voucher;

class VoucherPolicy
{
    /**
     * Determine whether the user can cancel the voucher.
     */
    public function cancel(User $user, Voucher $voucher): bool
    {
        if ($this->hasAnyRole($user, ['admin', 'administrator', 'administrador', 'super-admin'])) {
            return true;
        }

        if ($voucher->status === VoucherStatus::Cancelled) {
            return true;
        }

        if ($voucher->status === VoucherStatus::Draft) {
            return $this->hasAnyRole($user, [
                'operator',
                'operador',
                'manager',
                'gerente',
                'responsable_de_cobranzas',
            ]);
        }

        if ($voucher->status === VoucherStatus::Issued) {
            return $this->hasAnyRole($user, [
                'finance_manager',
                'finanzas',
                'gerente_financiero',
                'liquidador',
            ]);
        }

        return false;
    }

    private function hasAnyRole(User $user, array $roles): bool
    {
        if (!method_exists($user, 'hasAnyRole')) {
            return false;
        }

        return $user->hasAnyRole($roles);
    }
}
