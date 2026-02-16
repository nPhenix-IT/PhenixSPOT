<div>
  <input
    name="profile_id"
    class="price-input"
    type="radio"
    value="{{ $profile->id }}"
    id="profile{{ $profile->id }}"
    data-is-free="{{ (float) $displayPrice <= 0 ? 1 : 0 }}"
    required
  >
  <label class="price-badge {{ $badgeClass }}" for="profile{{ $profile->id }}">
    <div class="badge-header">
      <span class="badge-time">{{ $profile->name }}</span>
      <span class="badge-val">
        {{ (float) $displayPrice <= 0 ? 'Gratuit' : number_format($displayPrice, 0, ',', ' ') . ' FCFA' }}
      </span>
    </div>
    <ul class="badge-list">
      <li>{{ $profile->rate_limit ?? 'Débit standard' }}</li>
      <li>{{ $profile->data_limit ? round($profile->data_limit / (1024*1024*1024), 2) . ' Go' : 'Données illimitées' }}</li>
      <li>
        @if ($commissionPayer === 'client' && $commissionAmount > 0)
          Frais: {{ number_format($commissionAmount, 0, ',', ' ') }} FCFA
        @elseif ($commissionAmount > 0)
          Frais: pris en charge
        @else
          Frais: aucun
        @endif
      </li>
    </ul>
    <div class="badge-footer">
      <span class="badge-cta">Acheter</span>
    </div>
  </label>
</div>
