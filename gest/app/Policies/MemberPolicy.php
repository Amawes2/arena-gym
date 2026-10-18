<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canOperate($user);
    }

    public function view(User $user, Member $member): bool
    {
        return $this->canOperate($user) && $user->canAccessGym((int) $member->gym_id);
    }

    public function create(User $user): bool
    {
        return $this->canOperate($user);
    }

    public function update(User $user, Member $member): bool
    {
        return $this->view($user, $member);
    }

    public function delete(User $user, Member $member): bool
    {
        return $this->canManage($user) && $user->canAccessGym((int) $member->gym_id);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, Member $member): bool
    {
        return $this->delete($user, $member);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, Member $member): bool
    {
        return $this->delete($user, $member);
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
