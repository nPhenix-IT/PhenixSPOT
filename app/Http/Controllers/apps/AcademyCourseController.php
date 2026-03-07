<?php

namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;

class AcademyCourseController extends Controller
{
    private const META_FILE = 'app/internal_docs/pages.json';

    /**
     * @return array<int, array<string, string>>
     */
    private function fallbackModules(): array
    {
        return [
            [
                'title' => '01. Démarrage & Compte PhenixSPOT',
                'slug' => 'faq-account-setup',
                'excerpt' => 'Inscription, profil, abonnement, onboarding et checklist de démarrage.',
                'category' => 'Démarrage',
                'duration' => '12 min',
                'level' => 'Beginner',
                'rating' => '5.0',
                'rating_count' => '16',
                'image' => 'assets/img/pages/app-academy-tutor-1.png',
            ],
            [
                'title' => '02. Ajouter et configurer son MikroTik',
                'slug' => 'faq-router-mikrotik',
                'excerpt' => 'Ajout NAS, script d\'installation, tests RADIUS et template login.',
                'category' => 'Réseau',
                'duration' => '18 min',
                'level' => 'Beginner to Intermediate',
                'rating' => '5.0',
                'rating_count' => '22',
                'image' => 'assets/img/pages/app-academy-tutor-2.png',
            ],
            [
                'title' => '03. Hotspot, Profils et Vouchers',
                'slug' => 'faq-hotspot-vouchers',
                'excerpt' => 'Créer des profils, générer/imprimer des vouchers, gérer le cycle abonnement.',
                'category' => 'Hotspot',
                'duration' => '20 min',
                'level' => 'Intermediate',
                'rating' => '5.0',
                'rating_count' => '18',
                'image' => 'assets/img/pages/app-academy-tutor-3.png',
            ],
            [
                'title' => '04. Page de Vente, Paiements et Notifications',
                'slug' => 'faq-sales-payments',
                'excerpt' => 'Personnaliser la vente publique, commissions, clés API et parcours client.',
                'category' => 'Monétisation',
                'duration' => '17 min',
                'level' => 'Intermediate',
                'rating' => '4.9',
                'rating_count' => '14',
                'image' => 'assets/img/pages/app-academy-tutor-4.png',
            ],
            [
                'title' => '05. Wallet, Retraits et Reporting',
                'slug' => 'faq-wallet-reporting',
                'excerpt' => 'Pilotage financier, retraits, exports et bonnes pratiques d\'exploitation.',
                'category' => 'Finance',
                'duration' => '14 min',
                'level' => 'Beginner to Advanced',
                'rating' => '4.9',
                'rating_count' => '11',
                'image' => 'assets/img/pages/app-academy-tutor-5.png',
            ],
            [
                'title' => 'Guide Complet Utilisateur PhenixSPOT',
                'slug' => 'faq-pages',
                'excerpt' => 'Vision complète module par module: compte, routeurs, hotspot, ventes, wallet et reporting.',
                'category' => 'Documentation',
                'duration' => '35 min',
                'level' => 'Beginner to Advanced',
                'rating' => '5.0',
                'rating_count' => '31',
                'image' => 'assets/img/pages/app-academy-tutor-6.png',
            ],
        ];
    }

    public function index(): View
    {
        return view('content.apps.app-academy-course', [
            'courses' => $this->publishedCourses(),
        ]);
    }

    private function publishedCourses(): Collection
    {
        $path = storage_path(self::META_FILE);
        $decoded = [];
        if (File::exists($path)) {
            $payload = json_decode((string) File::get($path), true);
            if (is_array($payload)) {
                $decoded = $payload;
            }
        }

        $courses = collect($decoded)
            ->filter(fn(array $page) => (bool) ($page['is_published'] ?? false))
            ->map(function (array $page) {
                $academy = $page['academy'] ?? [];

                return [
                    'title' => (string) ($page['title'] ?? 'Documentation'),
                    'slug' => (string) ($page['slug'] ?? ''),
                    'excerpt' => (string) ($academy['excerpt'] ?? ''),
                    'category' => (string) ($academy['category'] ?? 'Documentation'),
                    'duration' => (string) ($academy['duration'] ?? '10 min'),
                    'level' => (string) ($academy['level'] ?? 'Beginner'),
                    'rating' => (string) ($academy['rating'] ?? '5.0'),
                    'rating_count' => (string) ($academy['rating_count'] ?? '1'),
                    'image' => (string) ($academy['image'] ?? 'assets/img/pages/app-academy-tutor-1.png'),
                    'updated_at' => (string) ($page['updated_at'] ?? ''),
                ];
            })
            ->values();

        foreach ($this->fallbackModules() as $module) {
            $viewPath = 'content.internal-docs.custom.' . $module['slug'];
            $existsAlready = $courses->contains(fn(array $course) => $course['slug'] === $module['slug']);
            if (!$existsAlready && ViewFacade::exists($viewPath)) {
                $module['updated_at'] = now()->toDateTimeString();
                $courses->push($module);
            }
        }

        return $courses->sortByDesc('updated_at')->values();
    }
}
