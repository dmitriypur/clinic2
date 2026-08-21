<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CuratorMedia;
use App\Models\Staff;
use Illuminate\Auth\Access\HandlesAuthorization;

class CuratorMediaPolicy
{
    use HandlesAuthorization;

    public function viewAny(Staff $staff): bool
    {
        return $staff->can('view_any_block');
    }

    public function view(Staff $staff, CuratorMedia $media): bool
    {
        return $staff->can('view_any_block');
    }

    public function create(Staff $staff): bool
    {
        return $staff->can('create_block');
    }

    public function update(Staff $staff, CuratorMedia $media): bool
    {
        return $staff->can('update_block');
    }

    public function delete(Staff $staff, CuratorMedia $media): bool
    {
        return $staff->can('delete_block') && ! $media->isUsedByBlocks();
    }

    public function deleteAny(Staff $staff): bool
    {
        return false;
    }
}
