<?php
namespace App\Enums;

enum ContractExpenseResponsibleParty: string
{
    case TENANT = 'tenant';
    case OWNER = 'owner';

    public function isTenant(): bool
    {
        return $this === self::TENANT;
    }

    public function isOwner(): bool
    {
        return $this === self::OWNER;
    }

}
