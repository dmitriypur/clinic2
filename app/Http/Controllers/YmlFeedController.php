<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\City;
use App\Services\YmlFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class YmlFeedController extends Controller
{
    public function __construct(
        private YmlFeedService $ymlFeedService
    ) {}

    public function generateDoctorsFeed(Request $request): JsonResponse
    {
        try {
            set_time_limit(120);

            $feeds = $this->ymlFeedService->generateDoctorsFeedsForActiveCities();
            $savedFeeds = $this->ymlFeedService->saveFeedsToFiles($feeds);

            return response()->json([
                'success' => true,
                'message' => 'Фиды врачей успешно сгенерированы',
                'files' => array_map(function (array $feed): array {
                    return [
                        'city_slug' => $feed['city_slug'],
                        'city_name' => $feed['city_name'],
                        'filename' => $feed['filename'],
                        'is_default' => $feed['is_default'],
                        'download_url' => route('yml-feed.download', $feed['filename']),
                        'public_url' => $feed['is_default']
                            ? route('yml-feed.show')
                            : route('yml-feed.show.city', ['city' => $feed['city_slug']]),
                    ];
                }, $savedFeeds),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при генерации фида: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadFeed(string $filename)
    {
        $filepath = 'public/' . $filename;
        
        if (!Storage::disk('public')->exists($filename)) {
            abort(404, 'Файл не найден');
        }
        
        return Storage::disk('public')->download($filename, $filename, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    public function showDoctorsFeed(?string $city = null)
    {
        $targetCity = null;
        if ($city !== null) {
            $targetCity = City::query()
                ->where('slug', $city)
                ->where('active', true)
                ->firstOrFail();
        }

        $feedContent = $this->ymlFeedService->generateDoctorsFeed($targetCity);

        return response($feedContent, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }
}
