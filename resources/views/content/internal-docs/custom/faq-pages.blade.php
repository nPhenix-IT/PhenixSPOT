@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'PhenixSPOT Academy - Guide Complet Utilisateur')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/page-faq.scss')
@endsection

@section('content')
<div class="faq-header d-flex flex-column justify-content-center align-items-center h-px-300 position-relative">
  <img src="{{ asset('assets/img/pages/header.png') }}" class="scaleX-n1-rtl faq-banner-img z-n1 rounded" alt="background image" />
  <h4 class="text-center mb-2">PhenixSPOT - Documentation Utilisateur Complète</h4>
  <p class="text-center mb-0 px-4">De la création du compte jusqu’à la vente de vouchers et au suivi financier.</p>
  <div class="mt-3">
    <a href="{{ route('docs.index') }}" class="btn btn-label-primary"><i class="icon-base ti tabler-arrow-left me-1"></i>Retour à la liste des FAQs</a>
  </div>
  <div class="input-wrapper mt-4 input-group input-group-merge">
    <span class="input-group-text"><i class="icon-base ti tabler-search icon-xs"></i></span>
    <input type="text" class="form-control" placeholder="Rechercher dans la documentation..." />
  </div>
</div>

<div class="row mt-6">
  <div class="col-lg-3 col-md-4 col-12 mb-md-0 mb-4">
    <div class="d-flex justify-content-between flex-column nav-align-left mb-2 mb-md-0">
      <ul class="nav nav-pills flex-column">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#start"><i class="icon-base ti tabler-rocket icon-sm faq-nav-icon me-1_5"></i><span class="align-middle">Démarrage</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mikrotik"><i class="icon-base ti tabler-router icon-sm faq-nav-icon me-1_5"></i><span class="align-middle">MikroTik & Routeurs</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#hotspot"><i class="icon-base ti tabler-wifi icon-sm faq-nav-icon me-1_5"></i><span class="align-middle">Hotspot & Vouchers</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#sales"><i class="icon-base ti tabler-shopping-cart icon-sm faq-nav-icon me-1_5"></i><span class="align-middle">Vente & Paiement</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#finance"><i class="icon-base ti tabler-wallet icon-sm faq-nav-icon me-1_5"></i><span class="align-middle">Wallet & Reporting</span></button></li>
      </ul>
      <div class="d-none d-md-block mt-4"><img src="{{ asset('assets/img/illustrations/girl-sitting-with-laptop.png') }}" class="img-fluid" width="240" alt="FAQ Image" /></div>
    </div>
  </div>

  <div class="col-lg-9 col-md-8 col-12">
    <div class="tab-content p-0">
      <div class="tab-pane fade show active" id="start" role="tabpanel">
        <div class="d-flex mb-4 gap-4 align-items-center">
          <span class="badge bg-label-primary rounded h-px-50 py-2"><i class="icon-base ti tabler-rocket icon-30px"></i></span>
          <div><h5 class="mb-0">1) Démarrage sur PhenixSPOT</h5><span>Création de compte, abonnement, onboarding intelligent</span></div>
        </div>
        <div id="accordionStart" class="accordion">
          <div class="card accordion-item active"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#start-1">Créer son compte et compléter son profil</button></h2><div id="start-1" class="accordion-collapse collapse show"><div class="accordion-body">Inscrivez-vous, confirmez vos informations, puis configurez votre profil. Le numéro de téléphone est utilisé pour les abonnements, notifications et services de paiement.</div></div></div>
          <div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#start-2">Choisir un forfait</button></h2><div id="start-2" class="accordion-collapse collapse"><div class="accordion-body">Allez dans <strong>Abonnement</strong>, choisissez un plan selon le nombre de routeurs, codes Wi-Fi, comptes VPN et options activées. Une fois le plan actif, vos modules deviennent opérationnels.</div></div></div>
          <div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#start-3">Suivre l’onboarding automatique</button></h2><div id="start-3" class="accordion-collapse collapse"><div class="accordion-body">Le système vous guide étape par étape (ajout routeur → profils → vouchers → page de vente → template login). Le widget global vous rappelle les étapes manquantes.</div></div></div>
        </div>

        <div class="alert alert-info mt-4 mb-0">
          <strong>Conseil pro :</strong> finalisez l’onboarding avant d’ouvrir la vente publique pour éviter les erreurs d’authentification client.
        </div>
      </div>

      <div class="tab-pane fade" id="mikrotik" role="tabpanel">
        <div class="d-flex mb-4 gap-4 align-items-center">
          <span class="badge bg-label-primary rounded h-px-50 py-2"><i class="icon-base ti tabler-router icon-30px"></i></span>
          <div><h5 class="mb-0">2) Ajouter et configurer son MikroTik / routeur</h5><span>NAS, scripts d’installation, intégration RADIUS</span></div>
        </div>
        <div id="accordionMikrotik" class="accordion">
          <div class="card accordion-item active"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#mk-1">Étape A — Ajouter un routeur dans PhenixSPOT</button></h2><div id="mk-1" class="accordion-collapse collapse show"><div class="accordion-body">Dans <strong>Mes Routeurs</strong>, créez votre routeur avec son IP, secret RADIUS, zone et paramètres réseau. Le NAS est synchronisé pour reconnaissance côté FreeRADIUS.</div></div></div>
          <div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mk-2">Étape B — Générer et exécuter le script MikroTik</button></h2><div id="mk-2" class="accordion-collapse collapse"><div class="accordion-body">Utilisez le générateur de script/commande d’installation MikroTik puis collez-la dans le terminal du routeur. Vérifiez ensuite la connectivité RADIUS depuis l’interface.</div></div></div>
          <div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mk-3">Étape C — Installer le template login Hotspot</button></h2><div id="mk-3" class="accordion-collapse collapse"><div class="accordion-body">Depuis <strong>Page de vente</strong>, générez le script du template login et importez-le dans MikroTik. Cela harmonise l’expérience client avec votre branding.</div></div></div>
        </div>

        <div class="card mt-4 shadow-none border">
          <div class="card-body">
            <h6 class="mb-3">Vidéo recommandée — Intégration MikroTik + RADIUS</h6>
            <div class="ratio ratio-16x9">
              <iframe src="https://www.youtube.com/embed/4sl5v1N2I6A" title="MikroTik Radius Setup" allowfullscreen></iframe>
            </div>
          </div>
        </div>
      </div>

      <div class="tab-pane fade" id="hotspot" role="tabpanel">
        <div class="d-flex mb-4 gap-4 align-items-center">
          <span class="badge bg-label-primary rounded h-px-50 py-2"><i class="icon-base ti tabler-ticket icon-30px"></i></span>
          <div><h5 class="mb-0">3) Profils, coupons et vouchers</h5><span>Paramétrage des limitations, génération et impression</span></div>
        </div>
        <div id="accordionHotspot" class="accordion">
          <div class="card accordion-item active"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#hs-1">Créer des profils Hotspot</button></h2><div id="hs-1" class="accordion-collapse collapse show"><div class="accordion-body">Dans <strong>Mes Profils</strong>, définissez vos offres: bande passante, durée, données, validité. Chaque profil représente une logique commerciale vendable.</div></div></div>
          <div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hs-2">Générer des vouchers</button></h2><div id="hs-2" class="accordion-collapse collapse"><div class="accordion-body">Dans <strong>Mes Codes</strong>, choisissez un profil puis générez en lot. Les codes peuvent être imprimés, exportés et vendus en direct (sans impression) via la page publique.</div></div></div>
          <div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hs-3">Gestion cycle abonnement</button></h2><div id="hs-3" class="accordion-collapse collapse"><div class="accordion-body">Les vouchers non utilisés sont automatiquement désactivés à l’expiration de l’abonnement et réactivés lors du renouvellement, selon la logique métier implémentée.</div></div></div>
        </div>

        <div class="card mt-4 shadow-none border">
          <div class="card-body">
            <h6 class="mb-3">Vidéo recommandée — Génération et vente de vouchers</h6>
            <div class="ratio ratio-16x9">
              <iframe src="https://www.youtube.com/embed/K8xYQ8zVw2Y" title="Hotspot Vouchers" allowfullscreen></iframe>
            </div>
          </div>
        </div>
      </div>

      <div class="tab-pane fade" id="sales" role="tabpanel">
        <div class="d-flex mb-4 gap-4 align-items-center">
          <span class="badge bg-label-primary rounded h-px-50 py-2"><i class="icon-base ti tabler-credit-card icon-30px"></i></span>
          <div><h5 class="mb-0">4) Vente publique, passerelles et automatisation</h5><span>Page de vente personnalisée, Money Fusion, notifications</span></div>
        </div>
        <div id="accordionSales" class="accordion">
          <div class="card accordion-item active"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sale-1">Configurer la page de vente</button></h2><div id="sale-1" class="accordion-collapse collapse show"><div class="accordion-body">Dans <strong>Page de vente</strong>, personnalisez les textes, styles, offres et stratégie de commission (vendeur/client/split). Cette page est votre canal de conversion principal.</div></div></div>
          <div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sale-2">Brancher les paiements</button></h2><div id="sale-2" class="accordion-collapse collapse"><div class="accordion-body">Activez vos clés passerelle selon votre forfait. Vous pouvez utiliser vos propres clés API ou opérer via wallet intégré selon la configuration disponible.</div></div></div>
          <div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sale-3">Livrer les codes aux clients</button></h2><div id="sale-3" class="accordion-collapse collapse"><div class="accordion-body">Les codes peuvent être affichés instantanément après paiement et envoyés via canaux de notification (SMS/Telegram selon options activées).</div></div></div>
        </div>
      </div>

      <div class="tab-pane fade" id="finance" role="tabpanel">
        <div class="d-flex mb-4 gap-4 align-items-center">
          <span class="badge bg-label-primary rounded h-px-50 py-2"><i class="icon-base ti tabler-report-analytics icon-30px"></i></span>
          <div><h5 class="mb-0">5) Wallet, retraits, rapports et exploitation</h5><span>Suivi opérationnel quotidien et pilotage business</span></div>
        </div>
        <div id="accordionFinance" class="accordion">
          <div class="card accordion-item active"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#fin-1">Wallet et retraits</button></h2><div id="fin-1" class="accordion-collapse collapse show"><div class="accordion-body">Consultez votre solde, vos ventes et commissions dans <strong>Mon Portefeuille</strong>. Les demandes de retrait respectent le minimum configuré (ex: 5000 FCFA).</div></div></div>
          <div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fin-2">Rapports et exports</button></h2><div id="fin-2" class="accordion-collapse collapse"><div class="accordion-body">Analysez les ventes/journaux par période et exportez en Excel/PDF pour votre comptabilité et le suivi des zones (routeurs).</div></div></div>
          <div class="card accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fin-3">Maintenance recommandée</button></h2><div id="fin-3" class="accordion-collapse collapse"><div class="accordion-body">Vérifiez régulièrement vos routeurs, la synchronisation NAS, l’état des paiements, et le scheduler serveur pour les tâches automatiques.</div></div></div>
        </div>

        <div class="alert alert-warning mt-4 mb-0">
          <strong>Checklist de fin de déploiement :</strong> routeur connecté, profils créés, vouchers générés, page de vente publiée, paiement fonctionnel, wallet vérifié.
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row my-6">
  <div class="col-12 text-center my-6">
    <div class="badge bg-label-primary">Support</div>
    <h4 class="my-2">Besoin d’aide supplémentaire ?</h4>
    <p class="mb-0">Contactez l’administrateur de votre instance PhenixSPOT ou enrichissez cette documentation depuis l’interface Super-admin.</p>
  </div>
</div>
@endsection
