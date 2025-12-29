<?php

namespace App\Http\Controllers;

use App\Clinic;
use App\Contracts\Services\PhoneService;
use App\Http\Requests\CallbackRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CallbackController extends Controller
{
    public function __invoke(CallbackRequest $request): void
    {
        $user = User::query()->updateOrCreate(
            ['phone' => resolve(PhoneService::class)->make($request->phone)],
            ['name' => $request->name],
        );
//        makingAnAppointment
        Clinic::callback([
            'uid' => $user->id,
            'name' => $user->name,
            'phone' => resolve(PhoneService::class)->make($user->phone),
            'guest' => Auth::guest(),
            'utm_source' => data_get($request, 'utm_source', Session::get('utm_source')),
            'utm_medium' => data_get($request, 'utm_medium', Session::get('utm_medium')),
            'utm_campaign' => data_get($request, 'utm_campaign', Session::get('utm_campaign')),
        ]);
    }
}
