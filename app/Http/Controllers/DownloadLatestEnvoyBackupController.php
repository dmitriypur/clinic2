<?php

namespace App\Http\Controllers;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadLatestEnvoyBackupController
{
    public static function canDownload(): bool
    {
        return Filament::auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function __invoke(Request $request): StreamedResponse|RedirectResponse
    {
        abort_unless(self::canDownload(), 403);

        $directory = config('backup.envoy_directory');
        $names = $directory ? @scandir($directory, SCANDIR_SORT_DESCENDING) : false;

        foreach ($names ?: [] as $name) {
            if (! preg_match('/\A[0-9]{8}-[0-9]{6}-[a-f0-9]{12}(?:-[a-zA-Z0-9]{6})?\.sql\.gz\z/', $name)) {
                continue;
            }

            $path = $directory.'/'.$name;
            if (is_link($path) || ! is_file($path)) {
                continue;
            }

            // Keep the file open so Envoy can prune it without interrupting the download.
            $stream = @fopen($path, 'rb');
            if ($stream === false) {
                break;
            }

            $headers = ['Content-Type' => 'application/gzip', 'Cache-Control' => 'private, no-store'];
            $stat = fstat($stream);
            if ($stat !== false) {
                $headers['Content-Length'] = (string) $stat['size'];
            }

            return response()->streamDownload(static function () use ($request, $stream): void {
                try {
                    if (! $request->isMethod('HEAD')) {
                        fpassthru($stream);
                    }
                } finally {
                    fclose($stream);
                }
            }, $name, $headers);
        }

        Notification::make()
            ->title('Резервная копия пока недоступна')
            ->warning()
            ->send();

        return redirect()->to(Filament::getUrl());
    }
}
