<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class MenuFileDownloadController extends Controller
{
    public function __invoke(string $encodedPath)
    {
        $path = base64_decode($encodedPath, true);

        if ($path === false || !str_starts_with($path, 'menu-files/')) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->download($path, basename($path));
    }
}
