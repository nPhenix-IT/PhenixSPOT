<div>
    @php
        $basePrice = (float) $profile->price;
        $feeAmount = $commissionPayer === 'client' ? max(0, (float) $commissionAmount) : 0;
        $totalPrice = (float) $displayPrice;
    @endphp


  <input
    name="profile_id"
    class="price-input"
    type="radio"
    value="{{ $profile->id }}"
    id="profile{{ $profile->id }}"
    data-is-free="{{ (float) $displayPrice <= 0 ? 1 : 0 }}"
    data-profile-name="{{ $profile->name }}"
    data-base-price="{{ $basePrice }}"
    data-fee="{{ $feeAmount }}"
    data-total="{{ $totalPrice }}"
    
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
    </ul>
    <div class="badge-footer">
      <span class="badge-cta">Acheter</span>
    </div>
  </label>
</div>
