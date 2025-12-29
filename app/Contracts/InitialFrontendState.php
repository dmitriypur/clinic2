<?php

namespace App\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface InitialFrontendState
{
    public function forUser(?Authenticatable $user);
}
