@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', '03. Hotspot, Profils et Vouchers')

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
      <div class="tab-pane fade show active" id="overview" role="tabpanel"><div class="d-flex mb-4 gap-4 align-items-center"><span class="badge bg-label-primary rounded h-px-50 py-2"><i class="icon-base ti tabler-ticket icon-30px"></i></span><div><h5 class="mb-0">Hotspot, profils et vouchers</h5><span>Concevoir, produire et distribuer vos codes</span></div></div><p>Définissez vos offres, créez vos vouchers et suivez leur cycle de vie selon l’abonnement.</p></div>
      <div class="tab-pane fade" id="steps" role="tabpanel"><div id="accordionH" class="accordion"><div class="card accordion-item active"><h2 class="accordion-header"><button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#h1">Créer des profils</button></h2><div id="h1" class="accordion-collapse collapse show"><div class="accordion-body">Paramétrez durée, données, bande passante et validité commerciale.</div></div></div><div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#h2">Générer les vouchers</button></h2><div id="h2" class="accordion-collapse collapse"><div class="accordion-body">Générez en lot, imprimez avec QR et servez-vous de la vente sans impression.</div></div></div><div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#h3">Gérer expiration/renouvellement</button></h2><div id="h3" class="accordion-collapse collapse"><div class="accordion-body">Les vouchers non utilisés suivent automatiquement l’état de l’abonnement utilisateur.</div></div></div></div><div class="card mt-4 shadow-none border"><div class="card-body"><h6 class="mb-3">Vidéo recommandée</h6><div class="ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/K8xYQ8zVw2Y" title="Vouchers" allowfullscreen></iframe></div></div></div></div>
      <div class="tab-pane fade" id="tips" role="tabpanel"><div class="alert alert-info mb-0"><strong>Astuce:</strong> maintenez un catalogue de profils simple (Starter/Pro/Premium) pour accélérer la vente.</div></div>
    </div>
  </div>
</div>
@endsection