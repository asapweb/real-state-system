<?php
namespace App\Enums;

enum ContractExpensePaidBy: string
{
    case TENANT = 'tenant';
    case OWNER = 'owner';
    case AGENCY = 'agency';

    public function isTenant(): bool
    {
        return $this === self::TENANT;
    }

    public function isOwner(): bool
    {
        return $this === self::OWNER;
    }

    public function isAgency(): bool
    {
        return $this === self::AGENCY;
    }

}
