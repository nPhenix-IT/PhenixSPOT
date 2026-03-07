<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View as ViewResponse;

class InternalDocPageController extends Controller
{
    private const META_FILE = 'app/internal_docs/pages.json';
    private const BLADE_DIR = 'resources/views/content/internal-docs/custom';
    private const FAQ_TEMPLATE_PATH = 'resources/views/content/internal-docs/templates/faq-default.blade.php';

    public function index(): ViewResponse
    {
        return view('content.admin.internal_docs.index', [
            'pages' => collect($this->loadPagesWithFilesystem())->sortByDesc('updated_at')->values()->all(),
        ]);
    }

    public function create(): ViewResponse
    {
        return view('content.admin.internal_docs.builder', [
            'mode' => 'create',
            'page' => [
                'title' => 'FAQ - Pages',
                'slug' => 'faq-pages',
                'is_published' => true,
                'blocks' => [],
                'template_mode' => true,
                'blade_content' => $this->defaultFaqTemplate(),
                'academy' => [
                    'category' => 'Documentation',
                    'duration' => '10 min',
                    'level' => 'Beginner',
                    'rating' => '5.0',
                    'rating_count' => '1',
                    'image' => 'assets/img/pages/app-academy-tutor-1.png',
                    'excerpt' => 'Documentation de démarrage pour configurer PhenixSPOT.',
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $pages = $this->loadPages();

        $slug = $data['slug'];
        abort_if(isset($pages[$slug]), 422, 'Ce slug existe déjà.');

        $page = [
            'title' => $data['title'],
            'slug' => $slug,
            'is_published' => (bool) $data['is_published'],
            'blocks' => $data['blocks'],
            'template_mode' => (bool) ($data['template_mode'] ?? false),
            'blade_content' => (string) ($data['blade_content'] ?? ''),
            'academy' => $data['academy'],
            'updated_at' => now()->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
        ];

        $pages[$slug] = $page;
        $this->writeBlade($page);
        $this->savePages($pages);

        return redirect()->route('admin.internal-docs.index')->with('success', 'Page documentation créée.');
    }

    public function edit(string $slug): ViewResponse
    {
        $pages = $this->loadPagesWithFilesystem();
        abort_unless(isset($pages[$slug]), 404);

        $page = $pages[$slug];
        if (empty($page['blade_content'])) {
            $page['blade_content'] = $this->readBladeContent($slug);
        }
        $page['template_mode'] = (bool) ($page['template_mode'] ?? true);
        $page['academy'] = $this->normalizeAcademyMeta($page['academy'] ?? []);

        return view('content.admin.internal_docs.builder', [
            'mode' => 'edit',
            'page' => $page,
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $pages = $this->loadPagesWithFilesystem();
        abort_unless(isset($pages[$slug]), 404);

        $data = $this->validatePayload($request);
        $newSlug = $data['slug'];

        if ($newSlug !== $slug && isset($pages[$newSlug])) {
            abort(422, 'Ce slug existe déjà.');
        }

        $page = [
            'title' => $data['title'],
            'slug' => $newSlug,
            'is_published' => (bool) $data['is_published'],
            'blocks' => $data['blocks'],
            'template_mode' => (bool) ($data['template_mode'] ?? false),
            'blade_content' => (string) ($data['blade_content'] ?? ''),
            'academy' => $data['academy'],
            'updated_at' => now()->toDateTimeString(),
            'created_at' => $pages[$slug]['created_at'] ?? now()->toDateTimeString(),
        ];

        unset($pages[$slug]);
        $pages[$newSlug] = $page;

        if ($newSlug !== $slug) {
            $oldBlade = base_path(self::BLADE_DIR . '/' . $slug . '.blade.php');
            if (File::exists($oldBlade)) {
                File::delete($oldBlade);
            }
        }

        $this->writeBlade($page);
        $this->savePages($pages);

        return redirect()->route('admin.internal-docs.index')->with('success', 'Page documentation mise à jour.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $pages = $this->loadPagesWithFilesystem();
        abort_unless(isset($pages[$slug]), 404);

        if (isset($pages[$slug])) {
            unset($pages[$slug]);
        }
        $this->savePages($pages);

        $bladePath = base_path(self::BLADE_DIR . '/' . $slug . '.blade.php');
        if (File::exists($bladePath)) {
            File::delete($bladePath);
        }

        return back()->with('success', 'Page documentation supprimée.');
    }

    public function show(Request $request, string $slug)
    {
        $pages = $this->loadPagesWithFilesystem();
        abort_unless(isset($pages[$slug]), 404);

        $page = $pages[$slug];
        if (!$page['is_published'] && !$request->user()->hasRole('Super-admin')) {
            abort(404);
        }

        $viewPath = 'content.internal-docs.custom.' . $slug;
        abort_unless(View::exists($viewPath), 404);

        return view($viewPath, ['pageTitle' => $page['title']]);
    }

    private function validatePayload(Request $request): array
    {
        $rawBlocks = $request->input('blocks');
        if (is_string($rawBlocks)) {
            $decodedBlocks = json_decode($rawBlocks, true);

            if (!is_array($decodedBlocks)) {
                throw ValidationException::withMessages([
                    'blocks' => 'Le contenu des blocs est invalide. Rechargez la page et réessayez.',
                ]);
            }

            $request->merge(['blocks' => $decodedBlocks]);
        }

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'slug' => 'required|string|max:120|regex:/^[a-z0-9\-]+$/',
            'is_published' => 'nullable|boolean',
            'blocks' => 'nullable|array',
            'blocks.*.type' => 'required|string|in:hero,text,callout,steps,faq,code,button',
            'blocks.*.title' => 'nullable|string|max:200',
            'blocks.*.content' => 'nullable|string',
            'blocks.*.items' => 'nullable|array',
            'blocks.*.label' => 'nullable|string|max:120',
            'blocks.*.url' => 'nullable|string|max:500',
            'blocks.*.variant' => 'nullable|string|max:40',
            'template_mode' => 'nullable|boolean',
            'blade_content' => 'nullable|string',
            'academy.category' => 'nullable|string|max:80',
            'academy.duration' => 'nullable|string|max:40',
            'academy.level' => 'nullable|string|max:40',
            'academy.rating' => 'nullable|string|max:10',
            'academy.rating_count' => 'nullable|string|max:20',
            'academy.image' => 'nullable|string|max:255',
            'academy.excerpt' => 'nullable|string|max:500',
        ]);

        $data['slug'] = trim((string) $data['slug']);
        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        $data['template_mode'] = (bool) ($data['template_mode'] ?? false);
        $data['blade_content'] = (string) ($data['blade_content'] ?? '');
        $data['academy'] = $this->normalizeAcademyMeta($data['academy'] ?? []);

        if ($data['template_mode'] && trim($data['blade_content']) === '') {
            throw ValidationException::withMessages([
                'blade_content' => 'Le template Blade est requis en mode code source.',
            ]);
        }

        if (!$data['template_mode'] && empty($data['blocks'])) {
            throw ValidationException::withMessages([
                'blocks' => 'Ajoutez au moins un bloc ou activez le mode code source.',
            ]);
        }

        return $data;
    }

    private function normalizeAcademyMeta(array $academy): array
    {
        return [
            'category' => (string) ($academy['category'] ?? 'Documentation'),
            'duration' => (string) ($academy['duration'] ?? '10 min'),
            'level' => (string) ($academy['level'] ?? 'Beginner'),
            'rating' => (string) ($academy['rating'] ?? '5.0'),
            'rating_count' => (string) ($academy['rating_count'] ?? '1'),
            'image' => (string) ($academy['image'] ?? 'assets/img/pages/app-academy-tutor-1.png'),
            'excerpt' => (string) ($academy['excerpt'] ?? ''),
        ];
    }


    private function loadPagesWithFilesystem(): array
    {
        $pages = $this->loadPages();
        $bladeFiles = File::glob(base_path(self::BLADE_DIR . '/*.blade.php')) ?: [];

        foreach ($bladeFiles as $file) {
            $slug = pathinfo($file, PATHINFO_FILENAME);
            if (isset($pages[$slug])) {
                continue;
            }

            $pages[$slug] = [
                'title' => str($slug)->replace('-', ' ')->title()->toString(),
                'slug' => $slug,
                'is_published' => true,
                'blocks' => [],
                'template_mode' => true,
                'blade_content' => (string) File::get($file),
                'academy' => $this->normalizeAcademyMeta([]),
                'updated_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
            ];
        }

        return $pages;
    }

    private function readBladeContent(string $slug): string
    {
        $path = base_path(self::BLADE_DIR . '/' . $slug . '.blade.php');

        if (!File::exists($path)) {
            return '';
        }

        return (string) File::get($path);
    }

    private function loadPages(): array
    {
        $metaPath = storage_path(self::META_FILE);
        if (!File::exists($metaPath)) {
            return [];
        }

        $decoded = json_decode((string) File::get($metaPath), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function savePages(array $pages): void
    {
        $metaPath = storage_path(self::META_FILE);
        File::ensureDirectoryExists(dirname($metaPath));
        File::put($metaPath, json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function writeBlade(array $page): void
    {
        $dir = base_path(self::BLADE_DIR);
        File::ensureDirectoryExists($dir);

        $templateMode = (bool) ($page['template_mode'] ?? false);
        if ($templateMode && trim((string) ($page['blade_content'] ?? '')) !== '') {
            $blade = (string) $page['blade_content'];
        } else {
            $html = $this->renderBlocks($page['blocks'] ?? []);

            $blade = "@extends('layouts/layoutMaster')\n\n";
            $blade .= "@section('title', '" . addslashes((string) $page['title']) . "')\n\n";
            $blade .= "@section('content')\n";
            $blade .= "<div class=\"container-xxl flex-grow-1 container-p-y\">\n";
            $blade .= "  <div class=\"card\">\n";
            $blade .= "    <div class=\"card-body\">\n";
            $blade .= $html . "\n";
            $blade .= "    </div>\n";
            $blade .= "  </div>\n";
            $blade .= "</div>\n";
            $blade .= "@endsection\n";
        }

        File::put($dir . '/' . $page['slug'] . '.blade.php', $blade);
    }

    private function defaultFaqTemplate(): string
    {
        $path = base_path(self::FAQ_TEMPLATE_PATH);

        if (!File::exists($path)) {
            return '';
        }

        return (string) File::get($path);
    }

    private function renderBlocks(array $blocks): string
    {
        $out = [];

        foreach ($blocks as $block) {
            $type = (string) ($block['type'] ?? 'text');
            $title = e((string) ($block['title'] ?? ''));
            $content = e((string) ($block['content'] ?? ''));

            if ($type === 'hero') {
                $out[] = "<div class=\"mb-4 p-4 rounded bg-label-primary\"><h3 class=\"mb-2\">{$title}</h3><p class=\"mb-0\">{$content}</p></div>";
                continue;
            }

            if ($type === 'text') {
                $out[] = "<div class=\"mb-4\"><h5 class=\"mb-2\">{$title}</h5><p class=\"mb-0\">{$content}</p></div>";
                continue;
            }

            if ($type === 'callout') {
                $variant = e((string) ($block['variant'] ?? 'info'));
                $out[] = "<div class=\"alert alert-{$variant} mb-4\"><strong>{$title}</strong><div>{$content}</div></div>";
                continue;
            }

            if ($type === 'steps') {
                $items = is_array($block['items'] ?? null) ? $block['items'] : [];
                $rows = collect($items)->map(fn($item) => '<li class="mb-1">' . e((string) $item) . '</li>')->implode('');
                $out[] = "<div class=\"mb-4\"><h5>{$title}</h5><ol class=\"mb-0\">{$rows}</ol></div>";
                continue;
            }

            if ($type === 'faq') {
                $items = is_array($block['items'] ?? null) ? $block['items'] : [];
                $rows = collect($items)->map(function ($row) {
                    $q = e((string) ($row['q'] ?? 'Question'));
                    $a = e((string) ($row['a'] ?? 'Réponse'));
                    return "<div class=\"mb-2\"><strong>{$q}</strong><p class=\"mb-0\">{$a}</p></div>";
                })->implode('');
                $out[] = "<div class=\"mb-4\"><h5>{$title}</h5>{$rows}</div>";
                continue;
            }

            if ($type === 'code') {
                $out[] = "<div class=\"mb-4\"><h5>{$title}</h5><pre class=\"p-3 rounded bg-light border\"><code>{$content}</code></pre></div>";
                continue;
            }

            if ($type === 'button') {
                $label = e((string) ($block['label'] ?? 'Action'));
                $url = e((string) ($block['url'] ?? '#'));
                $out[] = "<div class=\"mb-4\"><a href=\"{$url}\" class=\"btn btn-primary\">{$label}</a></div>";
            }
        }

        return implode("\n", $out);
    }
}
