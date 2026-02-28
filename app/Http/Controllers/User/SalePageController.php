<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SalePageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class SalePageController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $settings = $user->salePageSetting ?: new SalePageSetting([
            'commission_payer' => 'seller',
            'commission_percent' => config('fees.sales_commission_percent'),
            'login_primary_color' => '#3b82f6',
            'login_ticker_text' => '🚀 Bienvenue sur $(identity) ! Connexion ultra-rapide et illimitée à partir de 100 FCFA. Profitez-en maintenant ! ⚡ Support technique disponible 24/7.',
            'login_dns' => '10.1.254.1',
            'login_contact_phone_1' => '0100000000',
            'login_contact_label_1' => '01 00 000 000',
            'login_contact_phone_2' => '0500000000',
            'login_contact_label_2' => '05 00 000 000',
        ]);

        return view('content.sales-page.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'title' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:400',
            'primary_color' => 'nullable|string|max:20',
            'commission_payer' => 'required|in:seller,client',
            'login_primary_color' => 'nullable|string|max:20',
            'login_ticker_text' => 'nullable|string|max:300',
            'login_dns' => 'nullable|string|max:255',
            'login_contact_phone_1' => 'nullable|string|max:50',
            'login_contact_label_1' => 'nullable|string|max:50',
            'login_contact_phone_2' => 'nullable|string|max:50',
            'login_contact_label_2' => 'nullable|string|max:50',
        ]);

        $data['commission_percent'] = config('fees.sales_commission_percent');

        $user->salePageSetting()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return redirect()
            ->route('user.sales-page.edit')
            ->with('success', 'Page de vente mise à jour.');
    }

    public function downloadLoginTemplate()
    {
        $user = Auth::user();
        $settings = $user->salePageSetting ?: new SalePageSetting([
            'commission_payer' => 'seller',
            'commission_percent' => config('fees.sales_commission_percent'),
            'login_primary_color' => '#3b82f6',
            'login_ticker_text' => '🚀 Bienvenue sur $(identity) ! Connexion ultra-rapide et illimitée à partir de 100 FCFA. Profitez-en maintenant ! ⚡ Support technique disponible 24/7.',
            'login_dns' => '10.1.254.1',
            'login_contact_phone_1' => '0100000000',
            'login_contact_label_1' => '01 00 000 000',
            'login_contact_phone_2' => '0500000000',
            'login_contact_label_2' => '05 00 000 000',
        ]);
        $templatePath = storage_path('app/login_template');

        if (!File::exists($templatePath)) {
            abort(404, 'Template introuvable.');
        }

        $downloadName = 'login_template_' . ($user->slug ?? $user->id) . '.zip';
        $tempDir = storage_path('app/tmp_login_template_' . $user->id . '_' . time());
        $zipPath = storage_path('app/' . $downloadName);

        File::copyDirectory($templatePath, $tempDir);

        $primaryColor = $settings->login_primary_color ?? $settings->primary_color ?? '#3b82f6';
        $tickerText = $settings->login_ticker_text
            ?: '🚀 Bienvenue sur $(identity) ! Connexion ultra-rapide et illimitée à partir de 100 FCFA. Profitez-en maintenant ! ⚡ Support technique disponible 24/7.';
        $contactPhone1 = $settings->login_contact_phone_1 ?: '0100000000';
        $contactLabel1 = $settings->login_contact_label_1 ?: $contactPhone1;
        $contactPhone2 = $settings->login_contact_phone_2 ?: '0500000000';
        $contactLabel2 = $settings->login_contact_label_2 ?: $contactPhone2;
        $saleUrl = route('public.sale.show', $user->slug);

        $replacements = [
            '{{PRIMARY_COLOR}}' => $primaryColor,
            '{{PRIMARY_GLOW}}' => $this->hexToRgba($primaryColor, 0.5),
            '{{TICKER_TEXT}}' => $tickerText,
            '{{CONTACT_PHONE_1}}' => $contactPhone1,
            '{{CONTACT_LABEL_1}}' => $contactLabel1,
            '{{CONTACT_PHONE_2}}' => $contactPhone2,
            '{{CONTACT_LABEL_2}}' => $contactLabel2,
            '{{SALE_PAGE_URL}}' => $saleUrl,
        ];

        $this->replaceTemplatePlaceholders($tempDir, $replacements);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            File::deleteDirectory($tempDir);
            abort(500, 'Impossible de créer le fichier ZIP.');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($tempDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        File::deleteDirectory($tempDir);

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    public function previewLoginTemplate()
    {
        $user = Auth::user();
        $settings = $user->salePageSetting ?: new SalePageSetting([
            'commission_payer' => 'seller',
            'commission_percent' => config('fees.sales_commission_percent'),
            'login_primary_color' => '#3b82f6',
            'login_ticker_text' => '🚀 Bienvenue sur $(identity) ! Connexion ultra-rapide et illimitée à partir de 100 FCFA. Profitez-en maintenant ! ⚡ Support technique disponible 24/7.',
            'login_dns' => '10.1.254.1',
            'login_contact_phone_1' => '0100000000',
            'login_contact_label_1' => '01 00 000 000',
            'login_contact_phone_2' => '0500000000',
            'login_contact_label_2' => '05 00 000 000',
        ]);

        $templatePath = storage_path('app/login_template/login.html');
        $stylePath = storage_path('app/login_template/assets/style.css');

        if (!File::exists($templatePath) || !File::exists($stylePath)) {
            abort(404, 'Template introuvable.');
        }

        $primaryColor = $settings->login_primary_color ?? $settings->primary_color ?? '#3b82f6';
        $tickerText = $settings->login_ticker_text
            ?: '🚀 Bienvenue sur $(identity) ! Connexion ultra-rapide et illimitée à partir de 100 FCFA. Profitez-en maintenant ! ⚡ Support technique disponible 24/7.';
        $contactPhone1 = $settings->login_contact_phone_1 ?: '0100000000';
        $contactLabel1 = $settings->login_contact_label_1 ?: $contactPhone1;
        $contactPhone2 = $settings->login_contact_phone_2 ?: '0500000000';
        $contactLabel2 = $settings->login_contact_label_2 ?: $contactPhone2;
        $saleUrl = route('public.sale.show', $user->slug);

        $replacements = [
            '{{PRIMARY_COLOR}}' => $primaryColor,
            '{{PRIMARY_GLOW}}' => $this->hexToRgba($primaryColor, 0.5),
            '{{TICKER_TEXT}}' => $tickerText,
            '{{CONTACT_PHONE_1}}' => $contactPhone1,
            '{{CONTACT_LABEL_1}}' => $contactLabel1,
            '{{CONTACT_PHONE_2}}' => $contactPhone2,
            '{{CONTACT_LABEL_2}}' => $contactLabel2,
            '{{SALE_PAGE_URL}}' => $saleUrl,
        ];

        $html = File::get($templatePath);
        $style = File::get($stylePath);
        $style = str_replace(array_keys($replacements), array_values($replacements), $style);
        $html = str_replace('<link rel="stylesheet" href="assets/style.css">', '<style>' . $style . '</style>', $html);
        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        return response($html);
    }

    private function replaceTemplatePlaceholders(string $tempDir, array $replacements): void
    {
        $targetFiles = [
            $tempDir . '/login.html',
            $tempDir . '/assets/style.css',
        ];

        foreach ($targetFiles as $filePath) {
            if (!File::exists($filePath)) {
                continue;
            }
            $contents = File::get($filePath);
            $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);
            File::put($filePath, $contents);
        }
    }

    private function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $int = hexdec($hex);
        $r = ($int >> 16) & 255;
        $g = ($int >> 8) & 255;
        $b = $int & 255;
        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }
}
