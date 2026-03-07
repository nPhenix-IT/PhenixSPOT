@extends('layouts/layoutMaster')

@section('title', $mode === 'create' ? 'Créer doc interne' : 'Éditer doc interne')

@section('page-style')
<style>
  .builder-palette .btn { width: 100%; text-align: left; margin-bottom: .5rem; }
  .builder-canvas { min-height: 320px; border: 2px dashed #d9d6ff; border-radius: 12px; padding: 12px; background: #fafafe; }
  .builder-block { background: #fff; border: 1px solid #ecebff; border-radius: 10px; padding: 10px; margin-bottom: 10px; cursor: grab; }
  .builder-preview { min-height: 320px; border: 1px solid #ecebff; border-radius: 12px; padding: 14px; background: #fff; }
  .builder-preview .preview-badge { display:inline-block; padding:.2rem .5rem; border-radius:999px; font-size:.75rem; font-weight:600; background:#f0eeff; color:#7367f0; }
  .blade-editor-shell { border: 1px solid #dbdade; border-radius: .5rem; overflow: hidden; }
  .blade-editor-stage { min-height: 560px; width: 100%; }
  .blade-editor-fallback { min-height: 560px; font-family: Menlo, Monaco, Consolas, "Courier New", monospace; font-size: .85rem; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ $mode === 'create' ? 'Créer une page interne' : 'Éditer la page: ' . $page['title'] }}</h4>
    <a href="{{ route('admin.internal-docs.index') }}" class="btn btn-label-secondary">Retour</a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger mb-3">
      <strong>Impossible d'enregistrer la page :</strong>
      <ul class="mb-0 mt-1">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ $mode === 'create' ? route('admin.internal-docs.store') : route('admin.internal-docs.update', $page['slug']) }}" id="docBuilderForm">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif

    <div class="row g-3 mb-3">
      <div class="col-md-5">
        <label class="form-label">Titre</label>
        <input type="text" name="title" class="form-control" value="{{ old('title', $page['title']) }}" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" class="form-control" value="{{ old('slug', $page['slug']) }}" placeholder="guide-demarrage" required>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <div class="form-check form-switch me-3">
          <input class="form-check-input" type="checkbox" name="is_published" value="1" {{ old('is_published', $page['is_published']) ? 'checked' : '' }}>
          <label class="form-check-label">Publier</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="templateMode" name="template_mode" value="1" {{ old('template_mode', $page['template_mode'] ?? false) ? 'checked' : '' }}>
          <label class="form-check-label" for="templateMode">Mode code Blade</label>
        </div>
      </div>
    </div>


    <div class="card mb-3">
      <div class="card-header"><h6 class="mb-0">Métadonnées Academy (affichage côté utilisateur)</h6></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Catégorie</label>
            <input type="text" class="form-control" name="academy[category]" value="{{ old('academy.category', $page['academy']['category'] ?? 'Documentation') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label">Durée</label>
            <input type="text" class="form-control" name="academy[duration]" value="{{ old('academy.duration', $page['academy']['duration'] ?? '10 min') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label">Niveau</label>
            <input type="text" class="form-control" name="academy[level]" value="{{ old('academy.level', $page['academy']['level'] ?? 'Beginner') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label">Note</label>
            <input type="text" class="form-control" name="academy[rating]" value="{{ old('academy.rating', $page['academy']['rating'] ?? '5.0') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Nombre d'avis</label>
            <input type="text" class="form-control" name="academy[rating_count]" value="{{ old('academy.rating_count', $page['academy']['rating_count'] ?? '1') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Image (asset path)</label>
            <input type="text" class="form-control" name="academy[image]" value="{{ old('academy.image', $page['academy']['image'] ?? 'assets/img/pages/app-academy-tutor-1.png') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Résumé carte</label>
            <input type="text" class="form-control" name="academy[excerpt]" value="{{ old('academy.excerpt', $page['academy']['excerpt'] ?? '') }}">
          </div>
        </div>
      </div>
    </div>

    <div id="visualBuilderSection" class="row g-3">
      <div class="col-lg-3">
        <div class="card">
          <div class="card-header"><h6 class="mb-0">Blocs (drag & drop)</h6></div>
          <div class="card-body builder-palette">
            <button type="button" class="btn btn-label-primary" data-add="hero">Hero</button>
            <button type="button" class="btn btn-label-primary" data-add="text">Texte</button>
            <button type="button" class="btn btn-label-primary" data-add="callout">Callout</button>
            <button type="button" class="btn btn-label-primary" data-add="steps">Étapes</button>
            <button type="button" class="btn btn-label-primary" data-add="faq">FAQ</button>
            <button type="button" class="btn btn-label-primary" data-add="code">Code</button>
            <button type="button" class="btn btn-label-primary" data-add="button">Bouton</button>

            <hr>
            <div class="small text-muted mb-2">Blocs PhenixSPOT (presets)</div>
            <button type="button" class="btn btn-label-info" data-add-preset="coupon60">🎫 Coupon 60%</button>
            <button type="button" class="btn btn-label-info" data-add-preset="pricing3">💳 Table Pricing (3 plans)</button>
            <button type="button" class="btn btn-label-info" data-add-preset="whatsapp">🟢 WhatsApp CTA</button>
            <button type="button" class="btn btn-label-info" data-add-preset="mikrotik">🛜 MikroTik Login Setup</button>
            <button type="button" class="btn btn-label-info" data-add-preset="wallet">👛 Wallet & Commissions</button>
          </div>
        </div>
      </div>

      <div class="col-lg-9">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Canvas visuel</h6>
            <small class="text-muted">Déplacez les blocs pour réordonner</small>
          </div>
          <div class="card-body">
            <div id="builderCanvas" class="builder-canvas"></div>
            <div class="mt-3 d-flex justify-content-end gap-2">
              <button type="button" id="clearCanvas" class="btn btn-label-danger">Vider</button>
              <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
          </div>
        </div>

        <div class="card mt-3">
          <div class="card-header">
            <h6 class="mb-0">Aperçu rapide (live)</h6>
          </div>
          <div class="card-body">
            <div id="builderPreview" class="builder-preview"></div>
          </div>
        </div>
      </div>
    </div>

    <div id="codeBuilderSection" class="mt-3" style="display:none;">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Template Blade modifiable</h6>
          <small class="text-muted">Le fichier blade.php sera généré tel quel.</small>
        </div>
        <div class="card-body">
          <div class="alert alert-label-info mb-3">
            <i class="icon-base ti tabler-code me-2"></i>
            Éditeur moderne type Visual Studio Code (Monaco). Si le chargement échoue, le textarea fallback est utilisé.
          </div>
          <div class="blade-editor-shell mb-2">
            <div id="bladeEditorStage" class="blade-editor-stage"></div>
          </div>
          <textarea class="form-control blade-editor-fallback" name="blade_content" id="bladeContent" spellcheck="false">{{ old('blade_content', $page['blade_content'] ?? '') }}</textarea>
          <input type="hidden" name="blocks" id="blocksInput">
          <div class="mt-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.52.2/min/vs/loader.min.js"></script>
<script>
(() => {
  const initial = @json(old('blocks', $page['blocks'] ?? []));
  const canvas = document.getElementById('builderCanvas');
  const blocksInput = document.getElementById('blocksInput');
  const form = document.getElementById('docBuilderForm');
  const preview = document.getElementById('builderPreview');
  const templateModeInput = document.getElementById('templateMode');
  const visualBuilderSection = document.getElementById('visualBuilderSection');
  const codeBuilderSection = document.getElementById('codeBuilderSection');
  const state = Array.isArray(initial) ? [...initial] : [];
  const bladeContentTextarea = document.getElementById('bladeContent');
  const bladeEditorStage = document.getElementById('bladeEditorStage');
  let monacoEditor = null;

  const initializeMonaco = () => {
    if (!bladeContentTextarea || !bladeEditorStage || typeof window.require === 'undefined') return;

    window.require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.52.2/min/vs' } });
    window.require(['vs/editor/editor.main'], () => {
      monacoEditor = window.monaco.editor.create(bladeEditorStage, {
        value: bladeContentTextarea.value,
        language: 'php',
        theme: document.documentElement.classList.contains('dark-style') ? 'vs-dark' : 'vs',
        automaticLayout: true,
        fontSize: 13,
        minimap: { enabled: true },
        scrollBeyondLastLine: false,
        wordWrap: 'on',
      });

      monacoEditor.onDidChangeModelContent(() => {
        bladeContentTextarea.value = monacoEditor.getValue();
      });

      bladeContentTextarea.style.display = 'none';
    });
  };

  const toggleBuilderMode = () => {
    const templateMode = Boolean(templateModeInput?.checked);
    if (visualBuilderSection) {
      visualBuilderSection.style.display = templateMode ? 'none' : '';
    }
    if (codeBuilderSection) {
      codeBuilderSection.style.display = templateMode ? '' : 'none';
    }
    if (templateMode && blocksInput) {
      blocksInput.value = JSON.stringify(state);
    }
  };

  const templates = {
    hero: () => ({ type: 'hero', title: 'Titre Hero', content: 'Texte introductif...' }),
    text: () => ({ type: 'text', title: 'Titre section', content: 'Votre contenu...' }),
    callout: () => ({ type: 'callout', title: 'Important', content: 'Message important...', variant: 'info' }),
    steps: () => ({ type: 'steps', title: 'Étapes', items: ['Étape 1', 'Étape 2'] }),
    faq: () => ({ type: 'faq', title: 'FAQ', items: [{ q: 'Question ?', a: 'Réponse.' }] }),
    code: () => ({ type: 'code', title: 'Commande', content: '/ip address print' }),
    button: () => ({ type: 'button', label: 'Aller', url: '#' }),
  };

  const sync = () => {
    blocksInput.value = JSON.stringify(state);
  };

  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

  const renderPreview = () => {
    if (!preview) return;

    if (!state.length) {
      preview.innerHTML = '<div class="text-muted small">Aucun bloc ajouté pour le moment.</div>';
      return;
    }

    const html = state.map((block) => {
      if (block.type === 'hero') {
        return `<div class="mb-3 p-3 rounded bg-label-primary"><h4 class="mb-1">${escapeHtml(block.title)}</h4><p class="mb-0">${escapeHtml(block.content)}</p></div>`;
      }
      if (block.type === 'text') {
        return `<div class="mb-3"><h6 class="mb-1">${escapeHtml(block.title)}</h6><p class="mb-0">${escapeHtml(block.content)}</p></div>`;
      }
      if (block.type === 'callout') {
        const variant = escapeHtml(block.variant || 'info');
        return `<div class="alert alert-${variant} mb-3"><strong>${escapeHtml(block.title)}</strong><div>${escapeHtml(block.content)}</div></div>`;
      }
      if (block.type === 'steps') {
        const items = Array.isArray(block.items) ? block.items : [];
        return `<div class="mb-3"><h6>${escapeHtml(block.title)}</h6><ol class="mb-0">${items.map(i => `<li>${escapeHtml(i)}</li>`).join('')}</ol></div>`;
      }
      if (block.type === 'faq') {
        const items = Array.isArray(block.items) ? block.items : [];
        return `<div class="mb-3"><h6>${escapeHtml(block.title)}</h6>${items.map(i => `<div class="mb-1"><strong>${escapeHtml(i.q || 'Question')}</strong><div>${escapeHtml(i.a || '')}</div></div>`).join('')}</div>`;
      }
      if (block.type === 'code') {
        return `<div class="mb-3"><h6>${escapeHtml(block.title)}</h6><pre class="p-2 border rounded bg-light mb-0"><code>${escapeHtml(block.content)}</code></pre></div>`;
      }
      if (block.type === 'button') {
        return `<div class="mb-3"><a href="#" class="btn btn-primary btn-sm">${escapeHtml(block.label || 'Action')}</a></div>`;
      }
      return '';
    }).join('');

    preview.innerHTML = `<div class="preview-badge mb-3">Live Preview</div>${html}`;
  };

  const render = () => {
    canvas.innerHTML = '';

    if (!state.length) {
      canvas.innerHTML = '<div class="text-muted small">Ajoutez des blocs depuis la palette de gauche.</div>';
      sync();
      return;
    }

    state.forEach((block, index) => {
      const el = document.createElement('div');
      el.className = 'builder-block';
      el.dataset.index = String(index);

      const pretty = block.type.toUpperCase();
      let body = '';

      if (['hero', 'text', 'callout', 'code'].includes(block.type)) {
        body = `
          <input class="form-control form-control-sm mb-2" data-field="title" placeholder="Titre" value="${(block.title || '').replace(/"/g, '&quot;')}">
          <textarea class="form-control form-control-sm" rows="3" data-field="content" placeholder="Contenu">${block.content || ''}</textarea>
        `;
      } else if (block.type === 'button') {
        body = `
          <input class="form-control form-control-sm mb-2" data-field="label" placeholder="Label" value="${(block.label || '').replace(/"/g, '&quot;')}">
          <input class="form-control form-control-sm" data-field="url" placeholder="URL" value="${(block.url || '').replace(/"/g, '&quot;')}">
        `;
      } else if (block.type === 'steps') {
        body = `
          <input class="form-control form-control-sm mb-2" data-field="title" placeholder="Titre" value="${(block.title || '').replace(/"/g, '&quot;')}">
          <textarea class="form-control form-control-sm" rows="3" data-field="itemsText" placeholder="Une ligne = une étape">${(block.items || []).join('\n')}</textarea>
        `;
      } else if (block.type === 'faq') {
        body = `
          <input class="form-control form-control-sm mb-2" data-field="title" placeholder="Titre" value="${(block.title || '').replace(/"/g, '&quot;')}">
          <textarea class="form-control form-control-sm" rows="4" data-field="faqText" placeholder="Format: Question|Réponse">${(block.items || []).map(i => `${i.q || ''}|${i.a || ''}`).join('\n')}</textarea>
        `;
      }

      el.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>${pretty}</strong>
          <button type="button" class="btn btn-sm btn-label-danger" data-remove="${index}">Supprimer</button>
        </div>
        ${body}
      `;

      canvas.appendChild(el);
    });

    sync();
    renderPreview();
  };

  document.querySelectorAll('[data-add]').forEach(btn => {
    btn.addEventListener('click', () => {
      const type = btn.dataset.add;
      state.push(templates[type]());
      render();
    });
  });

  const presetFactories = {
    coupon60: () => ([
      { type: 'hero', title: 'Offre d\'essai Hotspot', content: 'Boostez vos ventes internet dès aujourd\'hui.' },
      { type: 'callout', title: 'Coupon Promo', content: 'Code: KDOS64E9 — Réduction -60% sur le plan Starter.', variant: 'success' },
      { type: 'button', label: 'Profiter maintenant', url: '#' },
    ]),
    pricing3: () => ([
      { type: 'text', title: 'Nos forfaits', content: 'Choisissez un plan adapté à votre zone hotspot.' },
      { type: 'steps', title: 'Starter — Pro — ISP', items: ['Starter: 1 routeur / 200 codes', 'Pro: 3 routeurs / 500 codes', 'ISP: Illimité / support prioritaire'] },
    ]),
    whatsapp: () => ([
      { type: 'callout', title: 'Support WhatsApp', content: 'Besoin d\'aide ? Contactez-nous au +225 01 02 03 04 05', variant: 'info' },
      { type: 'button', label: 'Ouvrir WhatsApp', url: 'https://wa.me/2250102030405' },
    ]),
    mikrotik: () => ([
      { type: 'text', title: 'Intégration Login MikroTik', content: 'Installez le template login avec le script prêt à coller.' },
      { type: 'code', title: 'Script installation', content: '/tool fetch url="https://votre-domaine/scripts/login-loader.rsc"; /import login-loader.rsc;' },
    ]),
    wallet: () => ([
      { type: 'callout', title: 'Wallet & commissions', content: 'Suivez vos ventes, commissions, retraits et solde net en temps réel.', variant: 'primary' },
      { type: 'faq', title: 'FAQ Finances', items: [{ q: 'Quand puis-je retirer ?', a: 'Retrait à partir de 5 000 FCFA.' }, { q: 'Qui paie la commission ?', a: 'Vous choisissez : vendeur, client ou split.' }] },
    ]),
  };

  document.querySelectorAll('[data-add-preset]').forEach(btn => {
    btn.addEventListener('click', () => {
      const key = btn.dataset.addPreset;
      const blocks = presetFactories[key] ? presetFactories[key]() : [];
      blocks.forEach(block => state.push(block));
      render();
    });
  });

  canvas.addEventListener('input', (e) => {
    const blockEl = e.target.closest('.builder-block');
    if (!blockEl) return;
    const idx = Number(blockEl.dataset.index);
    if (!Number.isInteger(idx) || !state[idx]) return;

    const field = e.target.dataset.field;
    const value = e.target.value;

    if (field === 'itemsText') {
      state[idx].items = value.split('\n').map(x => x.trim()).filter(Boolean);
    } else if (field === 'faqText') {
      state[idx].items = value.split('\n').map(line => {
        const [q, a] = line.split('|');
        return { q: (q || '').trim(), a: (a || '').trim() };
      }).filter(row => row.q || row.a);
    } else {
      state[idx][field] = value;
    }

    sync();
  });

  canvas.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-remove]');
    if (!btn) return;
    const idx = Number(btn.dataset.remove);
    state.splice(idx, 1);
    render();
  });

  document.getElementById('clearCanvas').addEventListener('click', () => {
    state.splice(0, state.length);
    render();
  });

  Sortable.create(canvas, {
    animation: 150,
    draggable: '.builder-block',
    onEnd: (evt) => {
      const moved = state.splice(evt.oldIndex, 1)[0];
      state.splice(evt.newIndex, 0, moved);
      render();
    }
  });

  form.addEventListener('submit', () => {
    sync();
    if (monacoEditor && bladeContentTextarea) {
      bladeContentTextarea.value = monacoEditor.getValue();
    }
  });
  templateModeInput?.addEventListener('change', toggleBuilderMode);

  initializeMonaco();
  render();
  toggleBuilderMode();
})();
</script>
@endsection
