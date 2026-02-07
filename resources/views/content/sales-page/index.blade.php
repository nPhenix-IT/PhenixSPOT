@extends('layouts/layoutMaster')

@section('title', 'Page de vente')

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
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
          <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('public.sale.show', auth()->user()->slug) }}" class="btn btn-label-secondary" target="_blank">
              Voir la page publique
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  const colorPicker = document.getElementById('primary_color_picker');
  const colorInput = document.getElementById('primary_color');

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
</script>
@endsection
