@extends('layouts/layoutMaster')

@section('title', 'Reporting & Exports')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1">Reporting des ventes</h4>
      <p class="text-muted mb-0">Suivi des ventes par routeur et par profil.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-primary" href="{{ route('user.reports.export-excel', ['period' => $period]) }}">Exporter Excel</a>
      <a class="btn btn-outline-secondary" href="{{ route('user.reports.export-pdf', ['period' => $period]) }}">Exporter PDF</a>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('user.reports.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label" for="period">Période</label>
          <select class="form-select" id="period" name="period">
            <option value="day" {{ $period === 'day' ? 'selected' : '' }}>Jour</option>
            <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Semaine</option>
            <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Mois</option>
            <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Année</option>
          </select>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary">Filtrer</button>
        </div>
        <div class="col-md-5 text-md-end">
          <div class="d-inline-flex gap-3">
            <div>
              <div class="text-muted small">Ventes</div>
              <div class="fw-bold">{{ $totals['sales'] }}</div>
            </div>
            <div>
              <div class="text-muted small">Montant total</div>
              <div class="fw-bold">{{ number_format($totals['amount'], 0, ',', ' ') }} FCFA</div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Ventes par routeur</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Routeur</th>
                  <th class="text-end">Ventes</th>
                  <th class="text-end">Montant</th>
                </tr>
              </thead>
              <tbody>
                @forelse($routerSales as $row)
                  <tr>
                    <td>{{ $row->label }}</td>
                    <td class="text-end">{{ $row->total_sales }}</td>
                    <td class="text-end">{{ number_format($row->total_amount, 0, ',', ' ') }} FCFA</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted">Aucune vente pour la période sélectionnée.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Ventes par profil</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Profil</th>
                  <th class="text-end">Ventes</th>
                  <th class="text-end">Montant</th>
                </tr>
              </thead>
              <tbody>
                @forelse($profileSales as $row)
                  <tr>
                    <td>{{ $row->label }}</td>
                    <td class="text-end">{{ $row->total_sales }}</td>
                    <td class="text-end">{{ number_format($row->total_amount, 0, ',', ' ') }} FCFA</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted">Aucune vente pour la période sélectionnée.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
