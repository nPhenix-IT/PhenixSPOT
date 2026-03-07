<?php

namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View as ViewFacade;

class AcademyCourseDetailsController extends Controller
{
    private const META_FILE = 'app/internal_docs/pages.json';

    public function index(string $slug)
    {
        $pages = $this->loadPages();
        $page = $pages[$slug] ?? null;
        $viewPath = 'content.internal-docs.custom.' . $slug;

        if (!$page && ViewFacade::exists($viewPath)) {
            $page = [
                'title' => 'Documentation PhenixSPOT',
                'is_published' => true,
            ];
        }

        abort_unless($page, 404);
        abort_unless((bool) ($page['is_published'] ?? false), 404);
        abort_unless(ViewFacade::exists($viewPath), 404);

        return view($viewPath, [
            'pageTitle' => (string) ($page['title'] ?? 'Documentation PhenixSPOT'),
        ]);
    }

    private function loadPages(): array
    {
        $path = storage_path(self::META_FILE);
        if (!File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);
        return is_array($decoded) ? $decoded : [];
    }
}
