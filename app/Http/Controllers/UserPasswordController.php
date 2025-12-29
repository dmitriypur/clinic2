<?php

namespace App\Http\Controllers;

use App\Contracts\Services\SmsService;
use App\Http\Requests\UserPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class UserPasswordController extends Controller
{
    public function __invoke(UserPasswordRequest $request, SmsService $smsService): JsonResponse
    {
        $user = User::query()->find($request->uid);

        abort_if(is_null($user), 404);

        $user->update(['password' => bcrypt($code = rand(1000, 9999))]);

        if (!App::isLocal()) {
            $smsService->send($user->phone, "Мама, я вижу. Код для входа в личный кабинет: $code");

            return response()->json();
        }

        return response()->json(['password' => $code]);
    }
}
