<?php

namespace App\Http\Controllers;

use App\Clinic;
use App\Contracts\Services\PhoneService;
use App\Http\Requests\CallbackRequest;
use App\Models\User;
use App\Services\CityService;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CallbackController extends Controller
{
    public function __construct(
        protected PhoneService $phoneService,
        protected CityService $cityService,
    ) {
        //
    }

    public function __invoke(CallbackRequest $request): JsonResponse
    {
        $cityName = $this->resolveCurrentCityName($request);

        $user = User::query()->updateOrCreate(
            ['phone' => $this->phoneService->make($request->phone)],
            ['name' => $request->name],
        );

        $response = Clinic::callback([
            'uid' => $user->id,
            'name' => $user->name,
            'phone' => $this->phoneService->make($user->phone),
            'guest' => Auth::guest(),
            'city' => $cityName,
            'source' => $request->input('source') ?: 'site',
            'type' => $request->input('type'),
            'utm_source' => data_get($request, 'utm_source', Session::get('utm_source')),
            'utm_medium' => data_get($request, 'utm_medium', Session::get('utm_medium')),
            'utm_campaign' => data_get($request, 'utm_campaign', Session::get('utm_campaign')),
            'utm_content' => data_get($request, 'utm_content', Session::get('utm_content')),
            'utm_term' => data_get($request, 'utm_term', Session::get('utm_term')),
        ]);

        return $this->callbackResponse($response);
    }

    protected function callbackResponse(ClientResponse $response): JsonResponse
    {
        $payload = $response->json();

        if (is_array($payload) && data_get($payload, 'response') === 'ok') {
            return response()->json([
                'response' => 'ok',
                'uid_request' => data_get($payload, 'uid_request'),
            ]);
        }

        $fail = is_array($payload) ? data_get($payload, 'fail') : null;

        if ($fail === 'InvalidPhone') {
            return response()->json([
                'message' => 'Проверьте номер телефона',
                'fail' => 'InvalidPhone',
                'errors' => [
                    'phone' => ['Проверьте номер телефона'],
                ],
            ], 422);
        }

        if ($fail === 'TooFrequent') {
            return response()->json([
                'message' => 'Заявка уже отправлена. Попробуйте повторить чуть позже.',
                'fail' => 'TooFrequent',
            ], 429);
        }

        if (in_array($fail, ['BadRequest', 'NoAuth', 'Internal'], true)) {
            Log::warning('Callback request failed in 1C.', [
                'fail' => $fail,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'message' => 'Не удалось отправить заявку. Попробуйте позже.',
                'fail' => $fail,
            ], 502);
        }

        Log::warning('Callback request returned unexpected 1C response.', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return response()->json([
            'message' => 'Не удалось отправить заявку. Попробуйте позже.',
            'fail' => 'UnexpectedResponse',
        ], 502);
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
