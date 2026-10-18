<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Membre = 'membre';
    case Receptionist = 'receptionist';
    case Coach = 'coach';

    public function isPlatformAdministrator(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canManageGym(): bool
    {
        return in_array($this, [self::Admin, self::Receptionist], true);
    }
}
