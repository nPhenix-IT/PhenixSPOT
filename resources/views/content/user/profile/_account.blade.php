@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-md-0 gap-2">
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'account' ? 'active' : '' }}" href="javascript:void(0);"><i class="icon-base ti tabler-users icon-sm me-1_5"></i> Compte</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'security' ? 'active' : '' }}" href="{{ route('user.profile', ['tab' => 'security']) }}"><i class="icon-base ti tabler-lock icon-sm me-1_5"></i> Sécurité</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'billing' ? 'active' : '' }}" href="{{ route('user.profile', ['tab' => 'billing']) }}"><i class="icon-base ti tabler-bookmark icon-sm me-1_5"></i> Facturation & Forfaits</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'notifications' ? 'active' : '' }}" href="{{ route('user.profile', ['tab' => 'notifications']) }}"><i class="icon-base ti tabler-bell icon-sm me-1_5"></i> Notifications</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'connections' ? 'active' : '' }}" href="{{ route('user.profile', ['tab' => 'connections']) }}"><i class="icon-base ti tabler-link icon-sm me-1_5"></i> Connexions</a>
        </li>
      </ul>
    </div>
    <div class="card mb-6">
        <h5 class="card-header">Informations du compte</h5>
        <div class="card-body">
    <style>
      .iti { width: 100%; }
      .iti__country-list { z-index: 9999; }
    </style>

    <form id="profileAccountForm" action="{{ route('user.profile.account.update') }}" method="POST" enctype="multipart/form-data">
      @csrf

      <div class="d-flex align-items-start align-items-sm-center gap-4 mb-6">
        <img src="{{ auth()->user()->profile_photo_url }}" alt="Photo de profil" class="d-block w-px-100 h-px-100 rounded" id="uploadedAvatar" />
        <div>
          <label for="profile_photo" class="btn btn-primary mb-2" tabindex="0">
            <span>Changer la photo</span>
            <input type="file" id="profile_photo" name="profile_photo" class="d-none" accept="image/png,image/jpeg,image/jpg,image/webp" />
          </label>
          <p class="text-muted mb-0">Formats acceptés : JPG, PNG, WEBP (max 2MB)</p>
          @error('profile_photo')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      </div>
      <div class="row gy-4 gx-6">
        <div class="col-md-6">
          <label for="name" class="form-label">Nom complet</label>
          <input class="form-control" type="text" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required />
          @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label for="email" class="form-label">Email</label>
          <input class="form-control" type="email" id="email" name="email" value="{{ old('email', auth()->user()->email) }}" required />
          @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label for="country" class="form-label">Pays</label>
          <select class="form-select" id="country" name="country">
            @php
              $countries = [
                'CI'=>'Côte d\'Ivoire','SN'=>'Sénégal','BF'=>'Burkina Faso','ML'=>'Mali','NE'=>'Niger','BJ'=>'Bénin','TG'=>'Togo','GN'=>'Guinée','GW'=>'Guinée-Bissau','CM'=>'Cameroun','GA'=>'Gabon','CG'=>'Congo-Brazzaville','CD'=>'RDC','CF'=>'Centrafrique','TD'=>'Tchad','GQ'=>'Guinée équatoriale','DJ'=>'Djibouti','KM'=>'Comores','MG'=>'Madagascar','MU'=>'Maurice','SC'=>'Seychelles','MR'=>'Mauritanie','TN'=>'Tunisie','DZ'=>'Algérie','MA'=>'Maroc'
              ];
              $savedCountry = old('country', auth()->user()->country_code);
            @endphp
            <option value="">Sélectionner un pays</option>
            @foreach($countries as $code => $label)
              <option value="{{ $code }}" {{ $savedCountry === $code ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          @error('country')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label for="phone_number" class="form-label">Numéro de téléphone</label>
          <input class="form-control" type="tel" id="phone_number" name="phone_number" value="{{ old('phone_number', auth()->user()->phone_number) }}" placeholder="0700000000" />
          <small class="text-muted">Le drapeau et l'indicatif sont détectés automatiquement.</small>
          @error('phone_number')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="mt-6">
        <button type="submit" class="btn btn-primary me-2">Enregistrer</button>
        <a href="{{ route('user.profile', ['tab' => 'account']) }}" class="btn btn-label-secondary">Annuler</a>
      </div>
    </form>
  </div>
</div>

<script>
  (function () {
    const cssId = 'intl-tel-input-css';
    if (!document.getElementById(cssId)) {
      const link = document.createElement('link');
      link.id = cssId;
      link.rel = 'stylesheet';
      link.href = 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.8.0/build/css/intlTelInput.css';
      document.head.appendChild(link);
    }

    function loadIntlTelInput() {
      return new Promise(function (resolve, reject) {
        if (window.intlTelInput) return resolve(window.intlTelInput);
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.8.0/build/js/intlTelInput.min.js';
        script.onload = function () { resolve(window.intlTelInput); };
        script.onerror = reject;
        document.body.appendChild(script);
      });
    }

    document.addEventListener('DOMContentLoaded', function () {
      const phoneInput = document.getElementById('phone_number');
      const countrySelect = document.getElementById('country');
      const form = document.getElementById('profileAccountForm');
      if (!phoneInput || !countrySelect || !form) return;

      loadIntlTelInput().then(function (intlTelInput) {
        const iti = intlTelInput(phoneInput, {
          initialCountry: countrySelect.value ? countrySelect.value.toLowerCase() : 'auto',
          separateDialCode: true,
          nationalMode: false,
          autoPlaceholder: 'aggressive',
          geoIpLookup: function (callback) {
            fetch('https://ipapi.co/json/')
              .then(function (res) { return res.json(); })
              .then(function (data) {
                const code = (data && data.country_code) ? data.country_code.toLowerCase() : 'ci';
                callback(code);
                if (!countrySelect.value) countrySelect.value = code.toUpperCase();
              })
              .catch(function () {
                callback('ci');
                if (!countrySelect.value) countrySelect.value = 'CI';
              });
          },
          utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.8.0/build/js/utils.js'
        });

        countrySelect.addEventListener('change', function () {
          if (countrySelect.value) iti.setCountry(countrySelect.value.toLowerCase());
        });

        phoneInput.addEventListener('countrychange', function () {
          const data = iti.getSelectedCountryData();
          if (data && data.iso2) countrySelect.value = data.iso2.toUpperCase();
        });

        form.addEventListener('submit', function () {
          const normalized = iti.getNumber();
          if (normalized) phoneInput.value = normalized;
        });
      }).catch(function () {
        // fallback silencieux
      });
    });
  })();
</script>

@endsection