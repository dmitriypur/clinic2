<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Filament\Forms\Components\Concerns\HasSafePublicFileTypes;
use Filament\Forms\Components\FileUpload;

final class SafeFileUpload extends FileUpload
{
    use HasSafePublicFileTypes;
}
