<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BookingDoctorsController extends Controller
{
    private const CACHE_TTL_SECONDS = 60;

    public function __invoke(Request $request): JsonResponse
    {
        $raw = (string)$request->query('uuids', '');

        $uuids = collect(explode(',', $raw))
            ->map(fn(string $uuid) => Str::lower(trim($uuid)))
            ->filter(fn(string $uuid) => Str::isUuid($uuid))
            ->unique()
            ->take(500)
            ->values();

        if ($uuids->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'hidden_uuids' => [],
                ],
            ]);
        }

        $cacheKey = 'booking-site-doctors:v1:' . sha1($uuids->sort()->implode(','));

        $payload = Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($uuids): array {
            $allMatchedUuids = Doctor::withoutGlobalScopes()
                ->whereIn('uuid', $uuids->all())
                ->pluck('uuid')
                ->map(fn(string $uuid): string => Str::lower(trim($uuid)))
                ->filter()
                ->unique()
                ->values();

            $doctors = Doctor::withoutGlobalScopes()
                ->publiclyVisible()
                ->with('media')
                ->whereIn('uuid', $uuids->all())
                ->get()
                ->map(function (Doctor $doctor): array {
                    return [
                        'uuid' => Str::lower((string)$doctor->uuid),
                        'ulid' => $doctor->ulid,
                        'name' => $doctor->name,
                        'surname' => $doctor->surname,
                        'full_name' => trim("{$doctor->surname} {$doctor->name}"),
                        'speciality' => $doctor->speciality,
                        'job_title' => $doctor->job_title,
                        'excerpt' => $doctor->excerpt,
                        'video_url' => $doctor->actual_video_url,
                        'avatar_url' => $doctor->getFirstMediaUrl('default', 'main') ?: null,
                        'avatar_image' => $doctor->avatar_image?->toHtml() ?? null,
                        'extra' => $doctor->extra ?? [],
                    ];
                })
                ->values();

            $visibleUuids = $doctors
                ->pluck('uuid')
                ->map(fn(string $uuid): string => Str::lower(trim($uuid)))
                ->filter()
                ->unique()
                ->values();

            $hiddenUuids = $allMatchedUuids
                ->diff($visibleUuids)
                ->values();

            return [
                'data' => $doctors->all(),
                'meta' => [
                    'hidden_uuids' => $hiddenUuids->all(),
                ],
            ];
        });

        return response()->json([
            'data' => $payload['data'] ?? [],
            'meta' => [
                'hidden_uuids' => $payload['meta']['hidden_uuids'] ?? [],
            ],
        ]);
    }
}
