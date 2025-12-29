<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Doctor $doctor): DoctorResource
    {
        return DoctorResource::make($doctor->load('media'));
    }
}
