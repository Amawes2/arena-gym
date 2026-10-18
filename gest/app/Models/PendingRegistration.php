<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'role', 'registration_data', 'code_hash', 'attempts', 'code_expires_at', 'registration_expires_at'])]
class PendingRegistration extends Model
{
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'registration_data' => 'encrypted:array',
            'attempts' => 'integer',
            'code_expires_at' => 'datetime',
            'registration_expires_at' => 'datetime',
        ];
    }
}
