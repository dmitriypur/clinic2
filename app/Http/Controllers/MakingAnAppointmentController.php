<?php

namespace App\Http\Controllers;

use App\Clinic;
use App\Contracts\Services\PhoneService;
use App\Http\Requests\MakingAnAppointmentRequest;
use Illuminate\Support\Facades\Session;

class MakingAnAppointmentController extends Controller
{
    public function __construct(protected PhoneService $phoneService)
    {
        //
    }

    public function __invoke(MakingAnAppointmentRequest $request): string
    {
        Clinic::makingAnAppointment([
            'doctor' => [
                'id' => $request->doctorId,
            ],
            'appointment' => [
                'dt_start' => now()->parse($request->date)->format('Y-m-d') . ' ' . $request->time . ':00',
                'comment' => ''
            ],
            'client' => [
                'mobile_phone' => $this->phoneService->make($request->phone),
                'last_name' => $request->lastName,
                'first_name' => $request->firstName,
                'second_name' => $request->middleName,
                'birthday' => now()->parse($request->birthdate)->format('Y-m-d'),
            ],
            'utm_source' => data_get($request, 'utm_source', Session::get('utm_source')),
            'utm_medium' => data_get($request, 'utm_medium', Session::get('utm_medium')),
            'utm_campaign' => data_get($request, 'utm_campaign', Session::get('utm_campaign')),
        ]);

        return now()->parse($request->date)->format('Y-m-d') . ' ' . $request->time . ':00';
    }
}
