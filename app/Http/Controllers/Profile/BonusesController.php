<?php

namespace App\Http\Controllers\Profile;

use App\Clinic;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BonusesController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = Auth::user();

        $user->bonuses = Clinic::getUser($user->id)['data']['bonus'] ?? 0;

        return view('profile.bonuses', compact('user'));
    }

}
