<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canOperate($user);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->canOperate($user) && $user->canAccessGym((int) $payment->gym_id);
    }

    public function create(User $user): bool
    {
        return $this->canOperate($user);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->canManage($user) && $user->canAccessGym((int) $payment->gym_id);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $this->update($user, $payment);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, Payment $payment): bool
    {
        return $this->update($user, $payment);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return $this->update($user, $payment);
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
