<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SalePageSetting;
use App\Models\User;
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

      // ✅ On ne demande plus les phones; labels servent aussi de "numéro" côté template
      'login_contact_label_1' => '01 00 000 000',
      'login_contact_label_2' => '05 00 000 000',

      // ✅ tarifs par défaut
      'login_pricing' => [
        ['time' => '1 Heure 30 Minutes', 'price' => '100 FCFA', 'style' => 'badge-blue'],
        ['time' => '1 Jour', 'price' => '300 FCFA', 'style' => 'badge-purple'],
        ['time' => '3 Jours', 'price' => '500 FCFA', 'style' => 'badge-purple'],
        ['time' => 'Semaine', 'price' => '1 000 FCFA', 'style' => 'badge-pink'],
        ['time' => '1 Mois', 'price' => '3 000 FCFA', 'style' => 'badge-emerald'],
      ],
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

      // ✅ FIX: autoriser split (50/50) en plus de seller/client
      'commission_payer' => 'required|in:seller,client,split',

      'login_primary_color' => 'nullable|string|max:20',
      'login_ticker_text' => 'nullable|string|max:300',
      'login_dns' => 'nullable|string|max:255',

      'login_contact_label_1' => 'nullable|string|max:50',
      'login_contact_label_2' => 'nullable|string|max:50',

      // JSON (string) ou array
      'login_pricing' => 'nullable',
    ]);

    $data['commission_percent'] = config('fees.sales_commission_percent');

    // ✅ Normalisation pricing
    $pricing = $request->input('login_pricing');
    if (is_string($pricing)) {
      $decoded = json_decode($pricing, true);
      $pricing = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($pricing)) $pricing = [];

    $clean = [];
    foreach ($pricing as $row) {
      if (!is_array($row)) continue;
      $time  = trim((string)($row['time'] ?? ''));
      $price = trim((string)($row['price'] ?? ''));
      $style = trim((string)($row['style'] ?? 'badge-blue'));

      if ($time === '' || $price === '') continue;
      if ($style === '') $style = 'badge-blue';

      $clean[] = ['time' => $time, 'price' => $price, 'style' => $style];
    }
    $data['login_pricing'] = $clean;

    $user->salePageSetting()->updateOrCreate(
      ['user_id' => $user->id],
      $data
    );

    // ✅ Optionnel: met à jour le fichier "storage/app/login_template/login.html"
    $this->applyPricingIntoLoginTemplate($user, $data['login_pricing']);

    return redirect()
      ->route('user.sales-page.edit')
      ->with('success', 'Page de vente mise à jour.');
  }

  public function downloadLoginTemplate()
  {
    $user = Auth::user();
    $settings = $this->getSettingsOrDefault($user);

    $templatePath = storage_path('app/login_template');
    if (!File::exists($templatePath)) abort(404, 'Template introuvable.');

    $downloadName = 'login_template_' . ($user->slug ?? $user->id) . '.zip';
    $tempDir = storage_path('app/tmp_login_template_' . $user->id . '_' . time());
    $zipPath = storage_path('app/' . $downloadName);

    File::copyDirectory($templatePath, $tempDir);

    $saleUrl = route('public.sale.show', $user->slug);

    $primaryColor = $settings->login_primary_color ?? $settings->primary_color ?? '#3b82f6';
    $tickerText = $settings->login_ticker_text
      ?: '🚀 Bienvenue sur $(identity) ! Connexion ultra-rapide et illimitée à partir de 100 FCFA. Profitez-en maintenant ! ⚡ Support technique disponible 24/7.';

    // ✅ Phone retiré => label sert aussi de tel:
    $label1 = $settings->login_contact_label_1 ?: '01 00 000 000';
    $label2 = $settings->login_contact_label_2 ?: '05 00 000 000';

    $replacements = [
      '{{PRIMARY_COLOR}}' => $primaryColor,
      '{{PRIMARY_GLOW}}' => $this->hexToRgba($primaryColor, 0.5),
      '{{TICKER_TEXT}}' => $tickerText,

      '{{CONTACT_PHONE_1}}' => $label1,
      '{{CONTACT_LABEL_1}}' => $label1,
      '{{CONTACT_PHONE_2}}' => $label2,
      '{{CONTACT_LABEL_2}}' => $label2,

      '{{SALE_PAGE_URL}}' => $saleUrl,
    ];

    // ✅ pricing dans le zip
    $this->applyPricingIntoLoginTemplateFromDir($tempDir, $settings->login_pricing ?? [], $saleUrl);
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
    $settings = $this->getSettingsOrDefault($user);

    $templatePath = storage_path('app/login_template/login.html');
    $stylePath = storage_path('app/login_template/assets/style.css');

    if (!File::exists($templatePath) || !File::exists($stylePath)) abort(404, 'Template introuvable.');

    $saleUrl = route('public.sale.show', $user->slug);

    $primaryColor = $settings->login_primary_color ?? $settings->primary_color ?? '#3b82f6';
    $tickerText = $settings->login_ticker_text
      ?: '🚀 Bienvenue sur $(identity) ! Connexion ultra-rapide et illimitée à partir de 100 FCFA. Profitez-en maintenant ! ⚡ Support technique disponible 24/7.';

    $label1 = $settings->login_contact_label_1 ?: '01 00 000 000';
    $label2 = $settings->login_contact_label_2 ?: '05 00 000 000';

    $replacements = [
      '{{PRIMARY_COLOR}}' => $primaryColor,
      '{{PRIMARY_GLOW}}' => $this->hexToRgba($primaryColor, 0.5),
      '{{TICKER_TEXT}}' => $tickerText,

      '{{CONTACT_PHONE_1}}' => $label1,
      '{{CONTACT_LABEL_1}}' => $label1,
      '{{CONTACT_PHONE_2}}' => $label2,
      '{{CONTACT_LABEL_2}}' => $label2,

      '{{SALE_PAGE_URL}}' => $saleUrl,
    ];

    $html = File::get($templatePath);
    $style = File::get($stylePath);

    // ✅ pricing dynamique (sans écrire sur disque)
    $pricingBlocks = $this->buildPricingBlocks($settings->login_pricing ?? [], $saleUrl);
    $html = $this->replacePricingArea($html, $pricingBlocks);

    $style = str_replace(array_keys($replacements), array_values($replacements), $style);
    $html = str_replace('<link rel="stylesheet" href="assets/style.css">', '<style>' . $style . '</style>', $html);
    $html = str_replace(array_keys($replacements), array_values($replacements), $html);

    return response($html);
  }

  /**
   * ✅ AUTH: commande /tool fetch tokenisée
   */
  public function loginTemplateInstallCommand(Request $request)
  {
    $user = Auth::user();

    $ttlSeconds = 3600;
    $expires = time() + $ttlSeconds;

    $payload = $user->id . '|' . $expires;
    $token = hash_hmac('sha256', $payload, config('app.key'));

    $loaderUrl = route('salespage.login.script.loader', [
      'user' => $user->slug ?? $user->id,
      'token' => $token,
      'expires' => $expires,
    ]);

    $cmd = "/tool fetch url=\"{$loaderUrl}\" mode=https check-certificate=no dst-path=phenixspot-login-loader.rsc; "
      . "/import phenixspot-login-loader.rsc; "
      . "/file remove phenixspot-login-loader.rsc";

    return response()->json([
      'script' => $cmd,
      'expires' => $expires,
    ]);
  }

  /**
   * ✅ PUBLIC: loader.rsc
   */
  public function loginTemplateScriptLoader(Request $request, string $user)
  {
    $userModel = $this->resolveScriptUser($user, $request);
    if (!$userModel) abort(403, 'Invalid or expired token.');

    $coreUrl = route('salespage.login.script.core', [
      'user' => $userModel->slug ?? $userModel->id,
    ]) . '?token=' . urlencode((string)$request->query('token'))
      . '&expires=' . urlencode((string)$request->query('expires'));

    $loader = <<<RSC
/tool fetch url="$coreUrl" mode=https check-certificate=no dst-path=phenixspot-login-core.rsc;
:delay 1s;
/import phenixspot-login-core.rsc;
/file remove phenixspot-login-core.rsc;
:delay 1s;
:log info "PHENIXSPOT: Installation login template terminée";
RSC;

    return response($loader, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
  }

  /**
   * ✅ PUBLIC: core.rsc
   * - sans file => renvoie le script d'install (download tout storage/app/login_template -> hotspot/)
   * - avec file => sert le fichier brut (sécurisé par token)
   */
  public function loginTemplateScriptCore(Request $request, string $user)
  {
    $userModel = $this->resolveScriptUser($user, $request);
    if (!$userModel) abort(403, 'Invalid or expired token.');

    // ✅ Mode "file server"
    if ($request->filled('file')) {
      return $this->serveLoginTemplateFile($request, $userModel);
    }

    $templateDir = storage_path('app/login_template');
    if (!File::exists($templateDir)) abort(404, 'Template introuvable.');

    // ✅ Scan récursif
    $allFiles = [];
    $dirs = [];

    $it = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($templateDir, \RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($it as $file) {
      if (!$file->isFile()) continue;
      $abs = $file->getRealPath();
      $rel = str_replace('\\', '/', substr($abs, strlen($templateDir) + 1));
      $allFiles[] = $rel;

      // collect dir (assets, assets/img, etc.)
      $dir = trim(str_replace('\\', '/', dirname($rel)), '.');
      if ($dir !== '' && $dir !== '/') {
        $dirs[$dir] = true;
      }
    }

    // sort dirs shortest first (assets before assets/img)
    $dirList = array_keys($dirs);
    usort($dirList, fn($a, $b) => strlen($a) <=> strlen($b));

    $token = (string)$request->query('token');
    $expires = (int)$request->query('expires');

    // ✅ Create dirs in hotspot/
    $mkdirLines = [];
    foreach ($dirList as $d) {
      $mikDir = 'hotspot/' . $d;
      $mikDir = str_replace('//', '/', $mikDir);
      $mkdirLines[] = ":do { /file make-directory name=\"$mikDir\"; } on-error={ :log warning \"PHENIXSPOT: dossier déjà existant ($mikDir)\"; };";
    }

    // ✅ Fetch all files (safe)
    $fetchLines = [];
    foreach ($allFiles as $rel) {
      $fileUrl = route('salespage.login.script.core', [
        'user' => $userModel->slug ?? $userModel->id,
      ]) . '?token=' . urlencode($token)
        . '&expires=' . urlencode((string)$expires)
        . '&file=' . urlencode($rel);

      $dst = 'hotspot/' . $rel;
      $dst = str_replace('//', '/', $dst);

      $fetchLines[] =
        ":do { /tool fetch url=\"$fileUrl\" mode=https check-certificate=no dst-path=\"$dst\"; } "
        . "on-error={ :log error \"PHENIXSPOT: échec téléchargement $dst\"; };";
    }

    // ✅ Domains
    $wgDomains = [
      'phenixspot.com',
      '*.phenixspot.com',
      'moneyfusion.net',
      '*.moneyfusion.net',
      'wave.com',
      '*.wave.com',
      'play.google.com',
      'apps.apple.com',
      'tools.applemediaservices.com',
      'cdn.jsdelivr.net',
      '*.jsdelivr.net',
    ];

    $wgLines = [];
    foreach ($wgDomains as $d) {
      // add only if not exists
      $wgLines[] =
        ":if ([:len [/ip hotspot walled-garden find where dst-host=\"$d\"]] = 0) do={ "
        . "/ip hotspot walled-garden add dst-host=\"$d\"; "
        . "} else={ :log info \"PHENIXSPOT: walled-garden déjà présent ($d)\"; };";
    }

    $core = "";
    $core .= "# ==========================================\n";
    $core .= "# PHENIXSPOT - HOTSPOT LOGIN TEMPLATE AUTO\n";
    $core .= "# ==========================================\n\n";

    $core .= ":log info \"PHENIXSPOT: création des dossiers hotspot/...\";\n";
    $core .= implode("\n", $mkdirLines) . "\n\n";

    $core .= ":log info \"PHENIXSPOT: téléchargement des fichiers login_template...\";\n";
    $core .= implode("\n", $fetchLines) . "\n\n";

    // ✅ IMPORTANT: certaines versions aiment un micro delay
    $core .= ":delay 1s;\n";

    $core .= ":log info \"PHENIXSPOT: html-directory=hotspot sur tous les profils\";\n";
    $core .= ":do { /ip hotspot profile set [find] html-directory=hotspot; } "
      . "on-error={ :log error \"PHENIXSPOT: impossible de définir html-directory\"; };\n\n";

    $core .= ":log info \"PHENIXSPOT: walled-garden\";\n";
    $core .= implode("\n", $wgLines) . "\n\n";

    $core .= ":log info \"PHENIXSPOT: OK\";\n";
    $core .= "# ==========================================\n";
    $core .= "# END CONFIG\n";
    $core .= "# ==========================================\n";

    return response($core, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
  }

  private function serveLoginTemplateFile(Request $request, User $userModel)
  {
    $rel = str_replace(['..', '\\'], ['', '/'], (string)$request->query('file'));
    $rel = ltrim($rel, '/');

    $baseDir = storage_path('app/login_template');
    $path = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);

    if (!File::exists($path) || !File::isFile($path)) abort(404, 'File not found.');

    // ✅ login.html => injecte placeholders + pricing
    if ($rel === 'login.html') {
      $settings = $this->getSettingsOrDefault($userModel);

      $saleUrl = route('public.sale.show', $userModel->slug);
      $primaryColor = $settings->login_primary_color ?? $settings->primary_color ?? '#3b82f6';
      $tickerText = $settings->login_ticker_text ?: '🚀 Bienvenue sur $(identity) ! Connexion ultra-rapide et illimitée à partir de 100 FCFA. Profitez-en maintenant ! ⚡ Support technique disponible 24/7.';

      $label1 = $settings->login_contact_label_1 ?: '01 00 000 000';
      $label2 = $settings->login_contact_label_2 ?: '05 00 000 000';

      $replacements = [
        '{{PRIMARY_COLOR}}' => $primaryColor,
        '{{PRIMARY_GLOW}}' => $this->hexToRgba($primaryColor, 0.5),
        '{{TICKER_TEXT}}' => $tickerText,

        '{{CONTACT_PHONE_1}}' => $label1,
        '{{CONTACT_LABEL_1}}' => $label1,
        '{{CONTACT_PHONE_2}}' => $label2,
        '{{CONTACT_LABEL_2}}' => $label2,

        '{{SALE_PAGE_URL}}' => $saleUrl,
      ];

      $html = File::get($path);
      $pricingBlocks = $this->buildPricingBlocks($settings->login_pricing ?? [], $saleUrl);
      $html = $this->replacePricingArea($html, $pricingBlocks);
      $html = str_replace(array_keys($replacements), array_values($replacements), $html);

      return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    // ✅ style.css => remplace couleurs
    if ($rel === 'assets/style.css') {
      $settings = $this->getSettingsOrDefault($userModel);
      $primaryColor = $settings->login_primary_color ?? $settings->primary_color ?? '#3b82f6';

      $css = File::get($path);
      $css = str_replace('{{PRIMARY_COLOR}}', $primaryColor, $css);
      $css = str_replace('{{PRIMARY_GLOW}}', $this->hexToRgba($primaryColor, 0.5), $css);

      return response($css, 200)->header('Content-Type', 'text/css; charset=UTF-8');
    }

    $mime = File::mimeType($path) ?: 'application/octet-stream';
    return response(File::get($path), 200)->header('Content-Type', $mime);
  }

  private function resolveScriptUser(string $userParam, Request $request): ?User
  {
    $token = (string)$request->query('token');
    $expires = (int)$request->query('expires');
    if (!$token || !$expires || $expires < time()) return null;

    $user = User::where('slug', $userParam)->first() ?: User::find($userParam);
    if (!$user) return null;

    $expected = $this->generateScriptTokenForUser($user, $expires);
    return hash_equals($expected, $token) ? $user : null;
  }

  private function generateScriptTokenForUser(User $user, int $expires): string
  {
    $payload = $user->id . '|' . $expires;
    return hash_hmac('sha256', $payload, config('app.key'));
  }

  private function getSettingsOrDefault(User $user): SalePageSetting
  {
    return $user->salePageSetting ?: new SalePageSetting([
      'commission_payer' => 'seller',
      'commission_percent' => config('fees.sales_commission_percent'),
      'login_primary_color' => '#3b82f6',
      'login_ticker_text' => '🚀 Bienvenue sur $(identity) ! Connexion ultra-rapide et illimitée à partir de 100 FCFA. Profitez-en maintenant ! ⚡ Support technique disponible 24/7.',
      'login_dns' => '10.1.254.1',
      'login_contact_label_1' => '01 00 000 000',
      'login_contact_label_2' => '05 00 000 000',
      'login_pricing' => [
        ['time' => '1 Heure 30 Minutes', 'price' => '100 FCFA', 'style' => 'badge-blue'],
      ],
    ]);
  }

  private function replaceTemplatePlaceholders(string $tempDir, array $replacements): void
  {
    $targetFiles = [
      $tempDir . '/login.html',
      $tempDir . '/assets/style.css',
    ];

    foreach ($targetFiles as $filePath) {
      if (!File::exists($filePath)) continue;
      $contents = File::get($filePath);
      $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);
      File::put($filePath, $contents);
    }
  }

  private function hexToRgba(string $hex, float $alpha): string
  {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $int = hexdec($hex);
    $r = ($int >> 16) & 255;
    $g = ($int >> 8) & 255;
    $b = $int & 255;
    return "rgba({$r}, {$g}, {$b}, {$alpha})";
  }

  private function buildPricingBlocks(array $pricing, string $saleUrl): string
  {
    if (empty($pricing)) return '';

    $out = [];
    foreach ($pricing as $row) {
      $time = htmlspecialchars((string)($row['time'] ?? ''), ENT_QUOTES, 'UTF-8');
      $price = htmlspecialchars((string)($row['price'] ?? ''), ENT_QUOTES, 'UTF-8');
      $style = preg_replace('/[^a-zA-Z0-9\-_]/', '', (string)($row['style'] ?? 'badge-blue'));
      if ($time === '' || $price === '') continue;

      $out[] =
        "<a href=\"{$saleUrl}\" class=\"price-badge {$style}\">\n" .
        " <span class=\"badge-time\">{$time}</span>\n" .
        " <span class=\"badge-val\">{$price}</span>\n" .
        " <span class=\"badge-cta\">Payer</span>\n" .
        "</a>";
    }
    return implode("\n", $out);
  }

  private function replacePricingArea(string $html, string $pricingBlocks): string
  {
    // Marqueurs recommandés
    if (str_contains($html, '<!-- PRICING_START -->') && str_contains($html, '<!-- PRICING_END -->')) {
      return preg_replace(
        '/<!-- PRICING_START -->(.*?)<!-- PRICING_END -->/s',
        "<!-- PRICING_START -->\n{$pricingBlocks}\n<!-- PRICING_END -->",
        $html
      ) ?? $html;
    }

    // Fallback div pricing-area
    return preg_replace(
      '/(<div class="pricing-area">)(.*?)(<\/div>)/s',
      '$1' . "\n" . $pricingBlocks . "\n" . '$3',
      $html,
      1
    ) ?? $html;
  }

  private function applyPricingIntoLoginTemplate(User $user, array $pricing): void
  {
    $path = storage_path('app/login_template/login.html');
    if (!File::exists($path)) return;

    $saleUrl = route('public.sale.show', $user->slug);

    $html = File::get($path);
    $blocks = $this->buildPricingBlocks($pricing, $saleUrl);
    $html = $this->replacePricingArea($html, $blocks);

    File::put($path, $html);
  }

  private function applyPricingIntoLoginTemplateFromDir(string $dir, array $pricing, string $saleUrl): void
  {
    $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'login.html';
    if (!File::exists($path)) return;

    $html = File::get($path);
    $blocks = $this->buildPricingBlocks($pricing, $saleUrl);
    $html = $this->replacePricingArea($html, $blocks);

    File::put($path, $html);
  }
}