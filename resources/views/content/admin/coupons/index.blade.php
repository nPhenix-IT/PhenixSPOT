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
        <button class="btn btn-primary" type="button" id="add-new-coupon-btn">
            <i class="ti ti-plus me-1"></i> Ajouter un bon
        </button>
    </div>
    <div class="card-datatable table-responsive">
        <table class="datatables-coupons table table-striped">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Valeur</th>
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
                    <div class="mb-3"><label class="form-label">Code</label><input type="text" name="code" class="form-control" required></div>
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