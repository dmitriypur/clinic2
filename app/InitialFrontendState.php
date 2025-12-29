<?php

namespace App;

use App\Contracts\InitialFrontendState as Contract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class InitialFrontendState implements Contract
{
    public function forUser(?Authenticatable $user): array
    {
        return [
            'user' => $user ? $this->currentUser($user) : null,
        ];
    }

    protected function currentUser(?Authenticatable $user): array
    {
        return [
            'name' => $user->name,
            'phone' => Str::after($user->phone, '8 '),
        ];
    }
}
