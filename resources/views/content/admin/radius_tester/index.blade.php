@extends('layouts/layoutMaster')
@section('title', 'Testeur RADIUS')

@section('page-script')
@vite(['resources/assets/js/app-radius-tester.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Administration /</span> Testeur RADIUS</h4>
<div class="row">
    <div class="col-xxl">
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Tester la connectivité d'un utilisateur</h5>
            </div>
            <div class="card-body">
                <form id="radiusTestForm">
                    <div class="row mb-3"><label class="col-sm-2 col-form-label">Nom d'utilisateur</label><div class="col-sm-10"><input type="text" class="form-control" name="username" required /></div></div>
                    <div class="row mb-3"><label class="col-sm-2 col-form-label">Mot de passe</label><div class="col-sm-10"><input type="password" class="form-control" name="password" required /></div></div>
                    <hr class="my-4">
                    <div class="row mb-3"><label class="col-sm-2 col-form-label">Adresse IP du NAS</label><div class="col-sm-10"><input type="text" class="form-control" name="address" value="127.0.0.1" required /></div></div>
                    <div class="row mb-3"><label class="col-sm-2 col-form-label">Port du NAS</label><div class="col-sm-10"><input type="number" class="form-control" name="port" value="1812" required /></div></div>
                    <div class="row mb-3"><label class="col-sm-2 col-form-label">Secret du NAS</label><div class="col-sm-10"><input type="text" class="form-control" name="secret" required /></div></div>
                    <div class="row justify-content-end"><div class="col-sm-10"><button type="submit" class="btn btn-primary">Lancer le test</button></div></div>
                </form>
            </div>
        </div>
        <div class="card" id="result-card" style="display: none;">
            <h5 class="card-header">Résultat du Test</h5>
            <div class="card-body">
                <pre class="bg-dark text-white p-3 rounded" id="test-output"></pre>
            </div>
        </div>
    </div>
</div>
@endsection
