@extends('layouts/layoutMaster')
@section('title', 'Bons de Réduction')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/app-coupon-list.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Administration /</span> Bons de Réduction</h4>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste des bons de réduction</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-danger" type="button" id="bulk-delete-coupons-btn" disabled>
                <i class="ti tabler-trash me-1"></i> Supprimer la sélection
            </button>
            <button class="btn btn-primary" type="button" id="add-new-coupon-btn">
                <i class="ti tabler-plus me-1"></i> Ajouter un bon
            </button>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table class="datatables-coupons table table-striped">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="coupon-select-all"></th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Valeur</th>
                    <th>Validité</th>
                    <th>Ciblage</th>
                    <th>Usage</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addCouponModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modal-title">Ajouter un Bon de Réduction</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form id="addCouponForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="coupon_id">
                    <div class="row">
                        <div class="col-md-8 mb-3"><label class="form-label">Code (laisser vide si auto-génération)</label><input type="text" name="code" class="form-control"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Préfixe auto</label><input type="text" name="prefix" class="form-control" placeholder="SAAS-"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Nombre à générer</label><input type="number" name="generate_count" class="form-control" min="1" value="1"></div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_generate" value="1" id="auto_generate_coupon">
                                <label class="form-check-label" for="auto_generate_coupon">Auto-générer les codes</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="fixed">Montant Fixe (FCFA)</option>
                                <option value="percent">Pourcentage (%)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label">Valeur</label><input type="number" name="value" class="form-control" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Début validité</label><input type="datetime-local" name="starts_at" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Fin validité</label><input type="datetime-local" name="ends_at" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Utilisateur ciblé</label>
                            <select name="user_id" class="form-select">
                                <option value="">Tous les utilisateurs</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Plan ciblé</label>
                            <select name="plan_id" class="form-select">
                                <option value="">Tous les plans</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-info py-2">Règle SaaS: un utilisateur ne peut utiliser un coupon qu'une seule fois.</div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active_coupon" checked>
                        <label class="form-check-label" for="is_active_coupon">Activer ce bon</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection