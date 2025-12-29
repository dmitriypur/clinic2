<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function update(UserUpdateRequest $request): JsonResponse
    {
        $user = User::query()->find($request->uid);

        $user->update(['uuid' => $request->contact]);

        return response()->json();
    }
}
