<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Filament\Forms\Components\Concerns\HasSafePublicFileTypes;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

final class SafeMediaLibraryFileUpload extends SpatieMediaLibraryFileUpload
{
    use HasSafePublicFileTypes;
}
