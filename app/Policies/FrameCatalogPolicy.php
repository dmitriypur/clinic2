<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Staff;
use App\Policies\Concerns\DeniesDemoMutations;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

abstract class FrameCatalogPolicy
{
    use DeniesDemoMutations;
    use HandlesAuthorization;

    protected const SUBJECT = '';

    protected const ALLOW_DELETE = false;

    public function viewAny(Staff $staff): bool
    {
        return $staff->can('view_any_'.static::SUBJECT);
    }

    public function view(Staff $staff, Model $record): bool
    {
        return $staff->can('view_'.static::SUBJECT);
    }

    public function create(Staff $staff): bool
    {
        return $staff->can('create_'.static::SUBJECT);
    }

    public function update(Staff $staff, Model $record): bool
    {
        return $staff->can('update_'.static::SUBJECT);
    }

    public function delete(Staff $staff, Model $record): bool
    {
        return static::ALLOW_DELETE && $staff->can('delete_'.static::SUBJECT);
    }

    public function deleteAny(Staff $staff): bool
    {
        return static::ALLOW_DELETE && $staff->can('delete_any_'.static::SUBJECT);
    }

    public function restore(Staff $staff, Model $record): bool
    {
        return false;
    }

    public function restoreAny(Staff $staff): bool
    {
        return false;
    }

    public function forceDelete(Staff $staff, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(Staff $staff): bool
    {
        return false;
    }

    public function replicate(Staff $staff, Model $record): bool
    {
        return $staff->can('replicate_'.static::SUBJECT);
    }

    public function reorder(Staff $staff): bool
    {
        return $staff->can('reorder_'.static::SUBJECT);
    }
}
