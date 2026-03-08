<?php

namespace App\Policies;

use App\Models\AcademicSchedule;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class AcademicSchedulePolicy
{
    use AuthorizesByRole;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AcademicSchedule $academicSchedule): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManageContent($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AcademicSchedule $academicSchedule): bool
    {
        return $this->canManageContent($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AcademicSchedule $academicSchedule): bool
    {
        return $this->canManageContent($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AcademicSchedule $academicSchedule): bool
    {
        return $this->canManageContent($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AcademicSchedule $academicSchedule): bool
    {
        return $this->canManageContent($user);
    }
}
