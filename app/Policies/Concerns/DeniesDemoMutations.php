<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\Staff;

trait DeniesDemoMutations
{
    public function before(Staff $staff, string $ability): ?bool
    {
        if ($staff->hasRole('demo') && ! in_array($ability, ['viewAny', 'view'], true)) {
            return false;
        }

        return null;
    }
}
