@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', '05. Wallet, Retraits et Reporting')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/page-faq.scss')
@endsection

@section('content')
<div class="faq-header d-flex flex-column justify-content-center align-items-center h-px-300 position-relative">
  <img src="{{ asset('assets/img/pages/header.png') }}" class="scaleX-n1-rtl faq-banner-img z-n1 rounded" alt="background image" />
  <h4 class="text-center mb-2">{{ $pageTitle ?? 'Documentation PhenixSPOT' }}</h4>
  <p class="text-center mb-0 px-4">Module opérationnel PhenixSPOT</p>
  <div class="mt-3">
    <a href="{{ route('docs.index') }}" class="btn btn-label-primary"><i class="icon-base ti tabler-arrow-left me-1"></i>Retour à la liste des FAQs</a>
  </div>
</div>

<div class="row mt-6">
  <div class="col-lg-3 col-md-4 col-12 mb-md-0 mb-4">
    <div class="d-flex justify-content-between flex-column nav-align-left mb-2 mb-md-0">
      <ul class="nav nav-pills flex-column">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview">Vue d'ensemble</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#steps">Étapes</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tips">Bonnes pratiques</button></li>
      </ul>
      <div class="d-none d-md-block mt-4"><img src="{{ asset('assets/img/illustrations/girl-sitting-with-laptop.png') }}" class="img-fluid" width="240" alt="FAQ Image" /></div>
    </div>
  </div>
  <div class="col-lg-9 col-md-8 col-12">
    <div class="tab-content p-0">
      <div class="tab-pane fade show active" id="overview" role="tabpanel"><div class="d-flex mb-4 gap-4 align-items-center"><span class="badge bg-label-primary rounded h-px-50 py-2"><i class="icon-base ti tabler-report-analytics icon-30px"></i></span><div><h5 class="mb-0">Wallet, retraits et reporting</h5><span>Pilotage financier et opérationnel</span></div></div><p>Suivez vos ventes, commissions et performances pour piloter votre activité hotspot.</p></div>
      <div class="tab-pane fade" id="steps" role="tabpanel"><div id="accordionF" class="accordion"><div class="card accordion-item active"><h2 class="accordion-header"><button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#f1">Consulter le wallet</button></h2><div id="f1" class="accordion-collapse collapse show"><div class="accordion-body">Vérifiez soldes, flux entrants, commissions et état des transactions.</div></div></div><div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#f2">Demander un retrait</button></h2><div id="f2" class="accordion-collapse collapse"><div class="accordion-body">Soumettez votre retrait selon le seuil minimum configuré.</div></div></div><div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#f3">Analyser les rapports</button></h2><div id="f3" class="accordion-collapse collapse"><div class="accordion-body">Exploitez les vues journalières/mensuelles/annuelles et exportez en Excel/PDF.</div></div></div></div></div>
      <div class="tab-pane fade" id="tips" role="tabpanel"><div class="alert alert-warning mb-0"><strong>Routine:</strong> contrôlez quotidiennement ventes, routeurs en ligne et échecs de paiement.</div></div>
    </div>
  </div>
</div>
@endsection