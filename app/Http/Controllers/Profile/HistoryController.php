<?php

namespace App\Http\Controllers\Profile;

use App\Clinic;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        $data = Clinic::getUser($user->id);

        $user->patients = $data['patients'] ?? [];

        return view('profile.history', compact('user'));
    }
}
