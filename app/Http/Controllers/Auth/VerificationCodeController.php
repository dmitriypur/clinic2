<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Services\PhoneService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerificationCodeRequest;
use App\Models\User;

class VerificationCodeController extends Controller
{
    public function __invoke(VerificationCodeRequest $request): void
    {
        User::query()->firstOrCreate(
            ['phone' => resolve(PhoneService::class)->make($request->phone)],
        );
    }
}
