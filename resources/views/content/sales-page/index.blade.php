@extends('layouts/layoutMaster')

@section('title', 'Page de vente')

@section('content')
<<<<<<< HEAD
<div class="row">
  <div class="col-lg-8">
    <div class="card">
=======
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card h-100">
>>>>>>> master
      <div class="card-header">
        <h5 class="mb-0">Personnaliser la page de vente</h5>
        <p class="text-muted mb-0">Modifiez le texte et choisissez qui paie la commission.</p>
      </div>
      <div class="card-body">
        @if (session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <form action="{{ route('user.sales-page.update') }}" method="POST">
          @csrf
          <div class="mb-4">
            <label class="form-label" for="title">Titre</label>
            <input type="text" id="title" name="title" class="form-control"
              value="{{ old('title', $settings->title) }}" placeholder="Ex: Forfaits WiFi de mon hotspot" />
            @error('title')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="mb-4">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3"
              placeholder="Décrivez votre offre...">{{ old('description', $settings->description) }}</textarea>
            @error('description')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
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
            @error('primary_color')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Commission</label>
            <div class="alert alert-info">
              Commission appliquée : <strong>{{ number_format($settings->commission_percent, 0, ',', ' ') }}%</strong>.
              Cette commission sera calculée automatiquement sur chaque vente.
            </div>
            <div class="d-flex flex-column gap-2">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="commission_payer" id="commission_seller"
                  value="seller" {{ old('commission_payer', $settings->commission_payer) === 'seller' ? 'checked' : '' }}>
                <label class="form-check-label" for="commission_seller">
                  Je prends en charge la commission (le client paie uniquement le prix affiché)
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="commission_payer" id="commission_client"
                  value="client" {{ old('commission_payer', $settings->commission_payer) === 'client' ? 'checked' : '' }}>
                <label class="form-check-label" for="commission_client">
                  Le client paie la commission (prix affiché + commission)
                </label>
              </div>
            </div>
            @error('commission_payer')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
<<<<<<< HEAD
=======
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
            @error('login_primary_color')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="mb-4">
            <label class="form-label" for="login_ticker_text">Texte défilant</label>
            <textarea id="login_ticker_text" name="login_ticker_text" class="form-control" rows="2"
              placeholder="Texte d'annonce...">{{ old('login_ticker_text', $settings->login_ticker_text) }}</textarea>
            @error('login_ticker_text')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="mb-4">
            <label class="form-label" for="login_dns">DNS du Hotspot</label>
            <input type="text" id="login_dns" name="login_dns" class="form-control"
              value="{{ old('login_dns', $settings->login_dns) }}" placeholder="phenixspot.wifi ou 10.1.254.1" />
            <div class="form-text">DNS à utiliser dans <code>/ip hotspot</code> (Ex: phenixspot.wifi).</div>
            @error('login_dns')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <label class="form-label" for="login_contact_phone_1">Contact 1 (Téléphone)</label>
              <input type="text" id="login_contact_phone_1" name="login_contact_phone_1" class="form-control"
                value="{{ old('login_contact_phone_1', $settings->login_contact_phone_1) }}" placeholder="0709532759" />
              @error('login_contact_phone_1')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="login_contact_label_1">Libellé contact 1</label>
              <input type="text" id="login_contact_label_1" name="login_contact_label_1" class="form-control"
                value="{{ old('login_contact_label_1', $settings->login_contact_label_1) }}" placeholder="07 09 532 759" />
              @error('login_contact_label_1')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <label class="form-label" for="login_contact_phone_2">Contact 2 (Téléphone)</label>
              <input type="text" id="login_contact_phone_2" name="login_contact_phone_2" class="form-control"
                value="{{ old('login_contact_phone_2', $settings->login_contact_phone_2) }}" placeholder="0501769806" />
              @error('login_contact_phone_2')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="login_contact_label_2">Libellé contact 2</label>
              <input type="text" id="login_contact_label_2" name="login_contact_label_2" class="form-control"
                value="{{ old('login_contact_label_2', $settings->login_contact_label_2) }}" placeholder="05 01 769 806" />
              @error('login_contact_label_2')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>
          </div>
>>>>>>> master
          <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('public.sale.show', auth()->user()->slug) }}" class="btn btn-label-secondary" target="_blank">
              Voir la page publique
            </a>
<<<<<<< HEAD
=======
            <a href="{{ route('user.sales-page.download-login-template') }}" class="btn btn-outline-primary">
              Télécharger le template login
            </a>
>>>>>>> master
          </div>
        </form>
      </div>
    </div>
  </div>
<<<<<<< HEAD
=======
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-0">Aperçu login MikroTik</h5>
          <p class="text-muted mb-0">Vérifiez vos couleurs et contacts.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#mikrotikHelpModal">
            Guide MikroTik
          </button>
          <a href="{{ route('user.sales-page.download-login-template') }}" class="btn btn-outline-primary btn-sm">
            Télécharger
          </a>
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

<div class="modal fade" id="mikrotikHelpModal" tabindex="-1" aria-labelledby="mikrotikHelpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title mb-1" id="mikrotikHelpModalLabel">Installer le template MikroTik</h5>
          <p class="text-muted mb-0">Suivez ces étapes pour mettre en place la page et autoriser les domaines.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-4">
        <div class="mb-4">
          <h6 class="mb-2">1) Importer la page de login</h6>
          <ol class="mb-0">
            <li>Téléchargez le template de login.</li>
            <li>Ouvrez Winbox ou WebFig &gt; <strong>Files</strong>.</li>
            <li>Déposez le contenu du zip dans <code>hotspot</code> (login.html, assets, etc.).</li>
          </ol>
        </div>
        <div class="mb-4">
          <h6 class="mb-2">2) Autoriser les domaines (walled-garden)</h6>
          <p class="text-muted">Ajoutez ces lignes dans le terminal MikroTik :</p>
          <div class="bg-light border rounded p-3">
            <pre class="mb-0"><code>/ip hotspot walled-garden
add dst-host=phenixspot.com
add dst-host=*.phenixspot.com
add dst-host=moneyfusion.net
add dst-host=*.moneyfusion.net
add dst-host=wave.com
add dst-host=*.wave.com
add dst-host=play.google.com
add dst-host=apps.apple.com
add dst-host=tools.applemediaservices.com
add dst-host=cdnjs.cloudflare.com</code></pre>
          </div>
        </div>
        <div>
          <h6 class="mb-2">3) Vérifier</h6>
          <p class="mb-0">Connectez-vous à votre hotspot et vérifiez que la page et les liens s’affichent correctement.</p>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
>>>>>>> master
</div>
@endsection

@section('page-script')
<script>
  const colorPicker = document.getElementById('primary_color_picker');
  const colorInput = document.getElementById('primary_color');
<<<<<<< HEAD
=======
  const loginColorPicker = document.getElementById('login_primary_color_picker');
  const loginColorInput = document.getElementById('login_primary_color');
>>>>>>> master

  if (colorPicker && colorInput) {
    colorPicker.addEventListener('input', event => {
      colorInput.value = event.target.value;
    });
    colorInput.addEventListener('input', event => {
      if (event.target.value) {
        colorPicker.value = event.target.value;
      }
    });
  }
<<<<<<< HEAD
=======

  if (loginColorPicker && loginColorInput) {
    loginColorPicker.addEventListener('input', event => {
      loginColorInput.value = event.target.value;
    });
    loginColorInput.addEventListener('input', event => {
      if (event.target.value) {
        loginColorPicker.value = event.target.value;
      }
    });
  }
>>>>>>> master
</script>
@endsection
