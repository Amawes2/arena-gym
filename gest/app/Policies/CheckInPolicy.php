<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CheckIn;
use App\Models\User;

class CheckInPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canOperate($user);
    }

    public function view(User $user, CheckIn $checkIn): bool
    {
        return $this->canOperate($user) && $user->canAccessGym((int) $checkIn->gym_id);
    }

    public function create(User $user): bool
    {
        return $this->canOperate($user);
    }

    public function update(User $user, CheckIn $checkIn): bool
    {
        return $this->canManage($user) && $user->canAccessGym((int) $checkIn->gym_id);
    }

    public function delete(User $user, CheckIn $checkIn): bool
    {
        return $this->update($user, $checkIn);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, CheckIn $checkIn): bool
    {
        return $this->update($user, $checkIn);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, CheckIn $checkIn): bool
    {
        return $this->update($user, $checkIn);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canManage($user);
    }

    private function canOperate(User $user): bool
    {
        return $user->isSuperAdmin()
            || in_array($user->roleEnum(), [UserRole::Admin, UserRole::Receptionist], true);
    }

    private function canManage(User $user): bool
    {
        return $user->isSuperAdmin() || $user->roleEnum() === UserRole::Admin;
    }
}
