<?php

namespace App\Http\Controllers;

use App\Enums\PageType;
use App\Models\Doctor;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class SitemapHtmlController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        $pages = Page::where('active', true)->with(['tags', 'category'])->get();

        // Разделяем страницы по типам
        $servicesPages = $pages->where('type', PageType::Services);
        $postsPages = $pages->where('type', PageType::Posts);
        $otherPages = $pages->filter(fn($page) => !in_array($page->type, [PageType::Services, PageType::Posts]));

        $doctors = Doctor::with('media')->get();
        $tags = Tag::has('pages')->get();

        return view('sitemap-html', compact('servicesPages', 'postsPages', 'otherPages', 'doctors', 'tags'));
    }
}
