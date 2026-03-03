@extends('layouts/layoutMaster')

@section('title', 'Page de vente')

@section('content')
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">Personnaliser la page de vente</h5>
        <p class="text-muted mb-0">Modifiez le texte et choisissez qui paie la commission.</p>
      </div>

      <div class="card-body">
        @if (session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form action="{{ route('user.sales-page.update') }}" method="POST" id="salePageForm">
          @csrf

          <div class="mb-4">
            <label class="form-label" for="title">Titre</label>
            <input type="text" id="title" name="title" class="form-control"
              value="{{ old('title', $settings->title) }}" placeholder="Ex: Forfaits WiFi de mon hotspot" />
            @error('title') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="mb-4">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3"
              placeholder="Décrivez votre offre...">{{ old('description', $settings->description) }}</textarea>
            @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="mb-4">
            <label class="form-label" for="primary_color">Couleur principale</label>
            <div class="d-flex align-items-center gap-3">
              <input type="color" id="primary_color_picker" class="form-control form-control-color"
                value="{{ old('primary_color', $settings->primary_color ?? '#4F46E5') }}" title="Choisir une couleur" />
              <input type="text" id="primary_color" name="primary_color" class="form-control"
                value="{{ old('primary_color', $settings->primary_color) }}" placeholder="#4F46E5" />
            </div>
            <div class="form-text">Couleur utilisée pour le bouton d'achat et les accents.</div>
            @error('primary_color') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="mb-4">
            <label class="form-label">Frais de transaction</label>
            <div class="alert alert-info">
              Frais de transaction appliqués :
              <strong>{{ number_format($settings->commission_percent, 0, ',', ' ') }}%</strong>.
              Ces frais seront appliqués automatiquement sur chaque vente.
            </div>

            <div class="d-flex flex-column gap-2">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="commission_payer" id="commission_seller"
                  value="seller" {{ old('commission_payer', $settings->commission_payer) === 'seller' ? 'checked' : '' }}>
                <label class="form-check-label" for="commission_seller">
                  Je prends en charge les frais (le client paie uniquement le prix affiché)
                </label>
              </div>

              <div class="form-check">
                <input class="form-check-input" type="radio" name="commission_payer" id="commission_client"
                  value="client" {{ old('commission_payer', $settings->commission_payer) === 'client' ? 'checked' : '' }}>
                <label class="form-check-label" for="commission_client">
                  Le client paie les frais (prix affiché + frais)
                </label>
              </div>

              {{-- ✅ NOUVEAU: split 50/50 --}}
              <div class="form-check">
                <input class="form-check-input" type="radio" name="commission_payer" id="commission_split"
                  value="split" {{ old('commission_payer', $settings->commission_payer) === 'split' ? 'checked' : '' }}>
                <label class="form-check-label" for="commission_split">
                  Partage 50/50 (client paie 50% des frais, moi je paie 50%)
                </label>
              </div>
            </div>

            @error('commission_payer') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <hr class="my-5">

          <h6 class="mb-4">Personnalisation du login MikroTik</h6>

          <div class="mb-4">
            <label class="form-label" for="login_primary_color">Couleur principale (Login)</label>
            <div class="d-flex align-items-center gap-3">
              <input type="color" id="login_primary_color_picker" class="form-control form-control-color"
                value="{{ old('login_primary_color', $settings->login_primary_color ?? '#3b82f6') }}" title="Choisir une couleur" />
              <input type="text" id="login_primary_color" name="login_primary_color" class="form-control"
                value="{{ old('login_primary_color', $settings->login_primary_color) }}" placeholder="#3b82f6" />
            </div>
            @error('login_primary_color') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="mb-4">
            <label class="form-label" for="login_ticker_text">Texte défilant</label>
            <textarea id="login_ticker_text" name="login_ticker_text" class="form-control" rows="2"
              placeholder="Texte d'annonce...">{{ old('login_ticker_text', $settings->login_ticker_text) }}</textarea>
            @error('login_ticker_text') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="mb-4">
            <label class="form-label" for="login_dns">DNS du Hotspot</label>
            <input type="text" id="login_dns" name="login_dns" class="form-control"
              value="{{ old('login_dns', $settings->login_dns) }}" placeholder="phenixspot.wifi ou 10.1.254.1" />
            <div class="form-text">DNS à utiliser dans <code>/ip hotspot</code>.</div>
            @error('login_dns') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <label class="form-label" for="login_contact_label_1">Contact 1</label>
              <input type="text" id="login_contact_label_1" name="login_contact_label_1" class="form-control"
                value="{{ old('login_contact_label_1', $settings->login_contact_label_1) }}" placeholder="07 09 532 759" />
              <div class="form-text">Affiché sur la page et utilisé aussi comme numéro (tel:)</div>
              @error('login_contact_label_1') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="login_contact_label_2">Contact 2</label>
              <input type="text" id="login_contact_label_2" name="login_contact_label_2" class="form-control"
                value="{{ old('login_contact_label_2', $settings->login_contact_label_2) }}" placeholder="05 01 769 806" />
              <div class="form-text">Affiché sur la page et utilisé aussi comme numéro (tel:)</div>
              @error('login_contact_label_2') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- ✅ Tarifs --}}
          <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div>
                <label class="form-label mb-0">Tarifs sur login.html</label>
                <div class="form-text">Modifiez puis “Enregistrer” : injection automatique dans le template.</div>
              </div>
              <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddPricing">
                <i class="ti tabler-plus me-1"></i> Ajouter un tarif
              </button>
            </div>

            <input type="hidden" name="login_pricing" id="login_pricing">

            <div class="table-responsive mt-3">
              <table class="table table-sm align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Durée</th>
                    <th>Prix</th>
                    <th>Style badge</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="pricingRows"></tbody>
              </table>
            </div>
          </div>

          <div class="d-flex gap-3 flex-wrap">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('public.sale.show', auth()->user()->slug) }}" class="btn btn-label-secondary" target="_blank">
              Voir la page publique
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-0">Aperçu login MikroTik</h5>
          <p class="text-muted mb-0">Vérifiez vos couleurs, contacts et tarifs.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#mikrotikInstallModal">
            Guide MikroTik
          </button>
          <!--<a href="{{ route('user.sales-page.download-login-template') }}" class="btn btn-outline-primary btn-sm">-->
          <!--Télécharger-->
          <!--</a>-->
        </div>
      </div>
      <div class="card-body d-flex flex-column">
        <div class="flex-grow-1 rounded overflow-hidden border" style="min-height: 640px;">
          <iframe src="{{ route('user.sales-page.login-template-preview') }}" title="Aperçu login"
            style="border:0; width:100%; height:100%;"></iframe>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ✅ MODAL INSTALL (refonte identique screenshot) --}}
<div class="modal fade" data-bs-backdrop="static" id="mikrotikInstallModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h4 class="modal-title fw-bold">Script d'installation</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body pt-3">
        <ul class="nav nav-tabs mb-4" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tabInstall" type="button" role="tab">
              Installer
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tabHelp" type="button" role="tab">
              Problèmes De Connexion ?
            </button>
          </li>
        </ul>

        <div class="tab-content">
          {{-- TAB 1 --}}
          <div class="tab-pane fade show active" id="tabInstall" role="tabpanel">
            <p class="text-muted mb-3">Copiez le script ci-dessous et collez-le dans le terminal de votre routeur.</p>

            <div class="d-flex align-items-stretch" style="gap:12px;">
              {{-- ✅ FIX: min-width:0 sur l’item flex pour éviter le débordement --}}
              <div class="flex-grow-1" style="min-width:0;">
                <div class="border rounded-3 px-3 py-2 bg-white" style="min-height:46px; display:flex; align-items:center; max-width:100%; overflow:hidden;">
                  <code id="mikrotikScriptText" style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; width:100%; max-width:100%;">
                    Chargement...
                  </code>
                </div>
              </div>

              <button type="button" class="btn btn-outline-primary px-3" id="btnCopyMikrotikScript"
                style="min-width:56px; display:flex; align-items:center; justify-content:center;">
                <i class="ti tabler-copy"></i>
              </button>
            </div>

            <div class="mt-4 p-3 rounded-3" style="background:#eef1ff; border:1px solid rgba(99,102,241,.25);">
              <div class="fw-semibold mb-2" style="color:#4f46e5;">Instructions</div>
              <ol class="mb-0" style="color:#4f46e5;">
                <li>Connectez-vous à votre routeur MikroTik via Winbox ou l'interface Web.</li>
                <li>Ouvrez la fenêtre du terminal (New Terminal).</li>
                <li>Collez le code fourni.</li>
                <li>Appuyez sur Entrée.</li>
              </ol>
            </div>

            <div class="mt-3 text-muted small">
              <ul class="mb-0" style="color:#4f46e5;">
                <li>Le script télécharge automatiquement tous les fichiers dans <code>hotspot/</code>, applique <code>html-directory=hotspot</code>.</li>
                <li><code>Assurez-vous d'avoir sauvegardé votre page de connexion avant d'appliquer ce script</code></li>
              </ul>
            </div>
          </div>

          {{-- TAB 2 --}}
          <div class="tab-pane fade" id="tabHelp" role="tabpanel">
            <div class="p-3 rounded-3 bg-light border">
              <div class="fw-semibold mb-2">Conseils en cas d'échec</div>
              <ul class="mb-0">
                <li>Si votre MikroTik bloque le HTTPS, laissez <code>check-certificate=no</code> (déjà inclus).</li>
                <li>Vérifiez que le routeur a accès à Internet (DNS/route par défaut).</li>
                <li>Essayez depuis Winbox (Terminal) plutôt que WebFig si possible.</li>
                <li>Regardez les logs : <code>/log print where message~"PHENIXSPOT"</code></li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
// Color sync
const colorPicker = document.getElementById('primary_color_picker');
const colorInput = document.getElementById('primary_color');
const loginColorPicker = document.getElementById('login_primary_color_picker');
const loginColorInput = document.getElementById('login_primary_color');

if (colorPicker && colorInput) {
  colorPicker.addEventListener('input', e => colorInput.value = e.target.value);
  colorInput.addEventListener('input', e => { if (e.target.value) colorPicker.value = e.target.value; });
}
if (loginColorPicker && loginColorInput) {
  loginColorPicker.addEventListener('input', e => loginColorInput.value = e.target.value);
  loginColorInput.addEventListener('input', e => { if (e.target.value) loginColorPicker.value = e.target.value; });
}

// ✅ Pricing UI (editable, persist)
const pricingHidden = document.getElementById('login_pricing');
const pricingRows = document.getElementById('pricingRows');
const btnAddPricing = document.getElementById('btnAddPricing');

const initialPricing = @json(old('login_pricing', $settings->login_pricing ?? []));

function getPricing() {
  try { return JSON.parse(pricingHidden.value || '[]'); } catch(e) { return []; }
}
function setPricing(arr) { pricingHidden.value = JSON.stringify(arr || []); }

function escAttr(v) {
  return String(v ?? '').replaceAll('&','&amp;').replaceAll('"','&quot;').replaceAll('<','&lt;').replaceAll('>','&gt;');
}

function renderPricing() {
  const arr = getPricing();
  pricingRows.innerHTML = '';
  arr.forEach((row, idx) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="text" class="form-control form-control-sm" value="${escAttr(row.time)}" data-k="time" data-i="${idx}"></td>
      <td><input type="text" class="form-control form-control-sm" value="${escAttr(row.price)}" data-k="price" data-i="${idx}"></td>
      <td>
        <select class="form-select form-select-sm" data-k="style" data-i="${idx}">
          ${['badge-blue','badge-purple','badge-pink','badge-emerald'].map(s => `<option value="${s}" ${row.style===s?'selected':''}>${s}</option>`).join('')}
        </select>
      </td>
      <td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger" data-del="${idx}">
          <i class="ti tabler-trash"></i>
        </button>
      </td>
    `;
    pricingRows.appendChild(tr);
  });
}

pricingRows.addEventListener('input', (e) => {
  const i = e.target.getAttribute('data-i');
  const k = e.target.getAttribute('data-k');
  if (i === null || !k) return;
  const arr = getPricing();
  arr[i] = arr[i] || {};
  arr[i][k] = e.target.value;
  setPricing(arr);
});

pricingRows.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-del]');
  if (!btn) return;
  const idx = parseInt(btn.getAttribute('data-del'), 10);
  const arr = getPricing();
  arr.splice(idx, 1);
  setPricing(arr);
  renderPricing();
});

btnAddPricing?.addEventListener('click', () => {
  const arr = getPricing();
  arr.push({ time: '1 Heure', price: '100 FCFA', style: 'badge-blue' });
  setPricing(arr);
  renderPricing();
});

// init
setPricing(Array.isArray(initialPricing) ? initialPricing : []);
renderPricing();

// ✅ Install script in modal
const installModal = document.getElementById('mikrotikInstallModal');
const scriptText = document.getElementById('mikrotikScriptText');
const btnCopy = document.getElementById('btnCopyMikrotikScript');

async function loadInstallCommand() {
  try {
    const res = await fetch("{{ route('user.sales-page.install-command') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
    const data = await res.json();
    scriptText.textContent = data.script || 'Erreur: script indisponible';
  } catch (e) {
    scriptText.textContent = 'Erreur: impossible de charger le script';
  }
}

installModal?.addEventListener('shown.bs.modal', loadInstallCommand);

btnCopy?.addEventListener('click', async () => {
  const text = scriptText.textContent || '';
  try {
    await navigator.clipboard.writeText(text);
    btnCopy.innerHTML = '<i class="ti tabler-check"></i>';
    setTimeout(() => btnCopy.innerHTML = '<i class="ti tabler-copy"></i>', 1200);
  } catch (e) {
    const ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
  }
});
</script>
@endsection