<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ArticleImport;
use App\Models\Block;
use App\Models\Page;
use App\Models\Staff;
use Illuminate\Support\Facades\Gate;

class ArticleImportPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return $this->canImportArticles($staff);
    }

    public function view(Staff $staff, ArticleImport $articleImport): bool
    {
        return $this->canImportArticles($staff);
    }

    private function canImportArticles(Staff $staff): bool
    {
        return Gate::forUser($staff)->allows('create', Page::class)
            && Gate::forUser($staff)->allows('create', Block::class);
    }
}
