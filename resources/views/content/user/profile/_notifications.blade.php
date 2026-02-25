@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-md-0 gap-2">
        <li class="nav-item">
          <a class="nav-link" href="{{ route('user.profile', ['tab' => 'account']) }}"><i class="icon-base ti tabler-users icon-sm me-1_5"></i> Compte</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('user.profile', ['tab' => 'security']) }}"><i class="icon-base ti tabler-lock icon-sm me-1_5"></i> Securité</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('user.profile', ['tab' => 'billing']) }}"><i class="icon-base ti tabler-bookmark icon-sm me-1_5"></i> Facturation & Forfaits</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="javascript:void(0);"><i class="icon-base ti tabler-bell icon-sm me-1_5"></i> Notifications</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('user.profile', ['tab' => 'connections']) }}"><i class="icon-base ti tabler-link icon-sm me-1_5"></i> Connections</a>
        </li>
      </ul>
    </div>
    <div class="card">
      <!-- Notifications -->
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4">
          <div>
            <h5 class="mb-0">Notifications Telegram</h5>
            <span class="card-subtitle">Recevez un rapport automatique pour chaque achat de voucher.</span>
          </div>
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#telegramHelpModal">
            <i class="icon-base ti tabler-info-circle me-1"></i> Guide Telegram
          </button>
        </div>
        @if (session('success'))
          <div class="alert alert-success mt-4">{{ session('success') }}</div>
        @endif
      </div>
      <div class="card-body">
        <form action="{{ route('user.profile.notifications') }}" method="POST">
          @csrf
          <div class="row g-4">
            <div class="col-md-6">
              <label class="form-label" for="telegram_bot_token">Telegram Bot Token</label>
              <input type="text" id="telegram_bot_token" name="telegram_bot_token" class="form-control"
                value="{{ old('telegram_bot_token', auth()->user()->telegram_bot_token) }}" />
              @error('telegram_bot_token')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="telegram_chat_id">Telegram Chat ID</label>
              <input type="text" id="telegram_chat_id" name="telegram_chat_id" class="form-control"
                value="{{ old('telegram_chat_id', auth()->user()->telegram_chat_id) }}" />
              @error('telegram_chat_id')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="mt-6 d-flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary me-3">Enregistrer</button>
            <button type="reset" class="btn btn-label-secondary">Annuler</button>
            <button type="submit" class="btn btn-outline-success" form="telegramTestForm">
              <i class="icon-base ti tabler-send me-1"></i> Tester Telegram
            </button>
          </div>
        </form>
        <form id="telegramTestForm" action="{{ route('user.profile.notifications.test-telegram') }}" method="POST" class="d-none">
          @csrf
          <input type="hidden" name="telegram_bot_token" value="{{ old('telegram_bot_token', auth()->user()->telegram_bot_token) }}" />
          <input type="hidden" name="telegram_chat_id" value="{{ old('telegram_chat_id', auth()->user()->telegram_chat_id) }}" />
        </form>
      </div>
      <!-- /Notifications -->
    </div>
  </div>
</div>

<div class="modal fade" id="telegramHelpModal" tabindex="-1" aria-labelledby="telegramHelpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center gap-3">
          <span class="badge bg-label-primary p-3 rounded-circle">
            <i class="icon-base ti tabler-brand-telegram"></i>
          </span>
          <div>
            <h5 class="modal-title mb-1" id="telegramHelpModalLabel">Guide Telegram — démarrage rapide</h5>
            <p class="text-muted mb-0">Créez un bot, récupérez votre token et configurez votre Chat ID en quelques minutes.</p>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-4">
        <div class="row g-4">
          <div class="col-lg-4">
            <div class="card bg-label-primary border-0 h-100">
              <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                  <span class="badge bg-primary">Étape 1</span>
                  <strong>Créer le bot</strong>
                </div>
                <p class="mb-3">Depuis Telegram, démarrez une conversation avec <strong>@BotFather</strong>.</p>
                <ul class="list-unstyled mb-0">
                  <li class="d-flex align-items-start gap-2 mb-2">
                    <i class="icon-base ti tabler-circle-check text-primary mt-1"></i>
                    Envoyez <code>/newbot</code>.
                  </li>
                  <li class="d-flex align-items-start gap-2 mb-2">
                    <i class="icon-base ti tabler-circle-check text-primary mt-1"></i>
                    Donnez un nom et un username.
                  </li>
                  <li class="d-flex align-items-start gap-2">
                    <i class="icon-base ti tabler-circle-check text-primary mt-1"></i>
                    Copiez le <strong>token</strong>.
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card bg-label-success border-0 h-100">
              <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                  <span class="badge bg-success">Étape 2</span>
                  <strong>Obtenir le Chat ID</strong>
                </div>
                <p class="mb-3">Envoyez un message à votre bot (ex: “Bonjour”).</p>
                <div class="bg-white rounded p-3 border">
                  <div class="text-muted small mb-1">URL à ouvrir :</div>
                  <code class="d-block">https://api.telegram.org/bot&lt;VOTRE_TOKEN&gt;/getUpdates</code>
                </div>
                <p class="mt-3 mb-0">Repérez <code>chat.id</code> dans la réponse JSON.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card bg-label-warning border-0 h-100">
              <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                  <span class="badge bg-warning">Étape 3</span>
                  <strong>Renseigner & Tester</strong>
                </div>
                <p class="mb-3">Collez vos identifiants dans le formulaire :</p>
                <ul class="list-unstyled mb-0">
                  <li class="d-flex align-items-start gap-2 mb-2">
                    <i class="icon-base ti tabler-key text-warning mt-1"></i>
                    <strong>Telegram Bot Token</strong>
                  </li>
                  <li class="d-flex align-items-start gap-2 mb-2">
                    <i class="icon-base ti tabler-message-2 text-warning mt-1"></i>
                    <strong>Telegram Chat ID</strong>
                  </li>
                  <li class="d-flex align-items-start gap-2">
                    <i class="icon-base ti tabler-send text-warning mt-1"></i>
                    Cliquez sur <strong>Tester Telegram</strong>.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <div class="accordion" id="telegramHelpAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingTips">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTips" aria-expanded="false" aria-controls="collapseTips">
                  Conseils & bonnes pratiques
                </button>
              </h2>
              <div id="collapseTips" class="accordion-collapse collapse" aria-labelledby="headingTips" data-bs-parent="#telegramHelpAccordion">
                <div class="accordion-body">
                  <ul class="mb-0">
                    <li>Ne partagez jamais votre token publiquement.</li>
                    <li>Pour un groupe Telegram, ajoutez le bot au groupe puis récupérez le <code>chat.id</code> du groupe.</li>
                    <li>Vous pouvez révoquer un token via <strong>@BotFather</strong> si nécessaire.</li>
                  </ul>
                </div>
              </div>
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