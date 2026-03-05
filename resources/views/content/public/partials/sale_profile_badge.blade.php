<div>
    @php
        $basePrice = (float) $profile->price;
        $feeAmount = max(0, (float) $commissionAmount);
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
        <!--{{ (float) $displayPrice <= 0 ? 'Gratuit' : number_format($displayPrice, 0, ',', ' ') . ' FCFA' }}-->
        {{ $basePrice <= 0 ? 'Gratuit' : number_format($basePrice, 0, ',', ' ') . ' FCFA' }}
      </span>
    </div>
    <ul class="badge-list">
      <li>{{ $profile->rate_limit ?? 'Débit standard' }}</li>
      <li>{{ $profile->data_limit ? round($profile->data_limit / (1024*1024*1024), 2) . ' Go' : 'Données illimitées' }}</li>
      <li>
            @php
                $s = (int) $profile->validity_period;
                if ($s >= 2592000) {
                    $val = round($s / 2592000);
                    $validity = $val . ' mois'; // "mois" est invariant en français
                } elseif ($s >= 604800) {
                    $val = round($s / 604800);
                    $validity = $val . ($val > 1 ? ' semaines' : ' semaine');
                } elseif ($s >= 86400) {
                    $val = round($s / 86400);
                    $validity = $val . ($val > 1 ? ' jours' : ' jour');
                } elseif ($s >= 3600) {
                    $val = round($s / 3600);
                    $validity = $val . ($val > 1 ? ' heures' : ' heure');
                } else {
                    $validity = 'Illimitée';
                }
            @endphp
            Validité : {{ $validity }}
        </li>
    </ul>
    <div class="badge-footer">
      <span class="badge-cta">Acheter</span>
    </div>
  </label>
</div>
