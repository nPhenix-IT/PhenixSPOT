@extends('layouts/layoutMaster')
@section('title', 'Gestion des Forfaits')
@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection
@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection
@section('page-script')
@vite(['resources/assets/js/app-plan-list.js'])
@endsection
@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Administration /</span> Gestion des Forfaits</h4>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste des forfaits</h5>
        <button class="btn btn-primary" type="button" id="add-new-plan-btn"><i class="icon-base ti tabler-plus me-1"></i> Ajouter un forfait</button>
    </div>
    <div class="card-datatable table-responsive">
        <table class="datatables-plans table table-striped">
            <thead class="table-light"><tr><th>Nom</th><th>Prix Mensuel</th><th>Statut</th><th>Actions</th></tr></thead>
        </table>
    </div>
</div>
<div class="modal fade" id="addPlanModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modal-title">Ajouter un Forfait</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form id="addPlanForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="plan_id">
                    <div class="mb-3"><label class="form-label">Nom du forfait</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Prix Mensuel (FCFA)</label><input type="number" name="price_monthly" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Prix Annuel (FCFA)</label><input type="number" name="price_annually" class="form-control" required></div>
                    </div>
                    <hr><h6 class="mb-3">Fonctionnalités</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Nb. Routeurs</label><input type="number" name="features[routers]" class="form-control" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Nb. Comptes VPN</label><input type="number" name="features[vpn_accounts]" class="form-control" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Nb. Coupons/mois</label><input type="number" name="features[vouchers]" class="form-control" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="features[coupon_model]" value="1" id="coupon_model"><label class="form-check-label" for="coupon_model">Modèle de coupon</label></div></div>
                        <div class="col-md-6 mb-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="features[sales_page]" value="1" id="sales_page"><label class="form-check-label" for="sales_page">Page de vente</label></div></div>
                    </div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked><label class="form-check-label" for="is_active">Activer ce forfait</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button><button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy"></i>  Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
