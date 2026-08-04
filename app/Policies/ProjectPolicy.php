<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\WorkspaceAccess;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return WorkspaceAccess::canAccessProject($user, $project);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return WorkspaceAccess::canAccessProject($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return WorkspaceAccess::canAccessProject($user, $project);
    }
}
