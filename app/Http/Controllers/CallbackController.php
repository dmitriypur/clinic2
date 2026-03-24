<?php

namespace App\Http\Controllers;

use App\Clinic;
use App\Contracts\Services\PhoneService;
use App\Http\Requests\CallbackRequest;
use App\Models\User;
use App\Services\CityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CallbackController extends Controller
{
    public function __construct(
        protected PhoneService $phoneService,
        protected CityService $cityService,
    ) {
        //
    }

    public function __invoke(CallbackRequest $request): void
    {
        $cityName = $this->resolveCurrentCityName($request);

        $user = User::query()->updateOrCreate(
            ['phone' => $this->phoneService->make($request->phone)],
            ['name' => $request->name],
        );

        Clinic::callback([
            'uid' => $user->id,
            'name' => $user->name,
            'phone' => $this->phoneService->make($user->phone),
            'guest' => Auth::guest(),
            'city' => $cityName,
            'utm_source' => data_get($request, 'utm_source', Session::get('utm_source')),
            'utm_medium' => data_get($request, 'utm_medium', Session::get('utm_medium')),
            'utm_campaign' => data_get($request, 'utm_campaign', Session::get('utm_campaign')),
        ]);
    }

    protected function resolveCurrentCityName(CallbackRequest $request): ?string
    {
        $requestCity = trim((string) $request->input('city', ''));

        if ($requestCity !== '') {
            return $requestCity;
        }

        $selectedCitySlug = (string) $request->cookie('selected_city', '');

        if ($selectedCitySlug !== '') {
            $selectedCity = $this->cityService->getCityBySlug($selectedCitySlug);
            if ($selectedCity) {
                return $selectedCity->name;
            }
        }

        return $this->cityService->getDefaultCity()?->name;
    }
}
