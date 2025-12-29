<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
            // Увеличиваем лимит времени выполнения
            set_time_limit(120);
            
            $feedContent = $this->ymlFeedService->generateDoctorsFeed();
            $filename = $this->ymlFeedService->saveFeedToFile($feedContent);
            
            return response()->json([
                'success' => true,
                'message' => 'Фид врачей успешно сгенерирован',
                'filename' => $filename,
                'download_url' => Storage::url($filename)
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

    public function showDoctorsFeed()
    {
        $feedContent = $this->ymlFeedService->generateDoctorsFeed();
        
        return response($feedContent, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }
}
