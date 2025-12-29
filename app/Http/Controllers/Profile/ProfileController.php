<?php

namespace App\Http\Controllers\Profile;

use App\Clinic;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Services\PhoneService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();

        $data = Clinic::getUser($user->id);

        return view('profile.show', compact('user'));
    }

    public function update(ProfileUpdateRequest $request): View
    {
        $user = Auth::user();

        $user->update([
            'last_name' => $request->validated('last_name'),
            'name' => $request->validated('name'),
            'middle_name' => $request->validated('middle_name'),
            'birthday' => $request->validated('birthday'),
            'phone' => resolve(PhoneService::class)->make($request->validated('phone')),
            'email' => $request->validated('email'),
            'accept_sms_notifications' => $request->accept_sms_notifications ?? 0,
            'accept_sms_promotions' => $request->accept_sms_promotions ?? 0,
        ]);

        return view('profile.show', compact('user'))->with('successMessage', 'Данные обновлены успешно');
    }
}
