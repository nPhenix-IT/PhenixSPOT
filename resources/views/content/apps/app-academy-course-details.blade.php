@extends('layouts/layoutMaster')

@section('title', 'Academy Course Details - Apps')

@section('vendor-style')
@vite('resources/assets/vendor/libs/plyr/plyr.scss')
@endsection

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-academy-details.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/plyr/plyr.js')
@endsection

@section('page-script')
@vite('resources/assets/js/app-academy-course-details.js')
@endsection

@section('content')
<div class="row g-6">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-6 gap-2">
          <div class="me-1">
            <h5 class="mb-0">{{ $course['title'] }}</h5>
            <p class="mb-0">Documentation <span class="fw-medium text-heading">PhenixSPOT</span></p>
          </div>
          <div class="d-flex align-items-center">
            <span class="badge bg-label-danger">{{ $course['category'] }}</span>
          </div>
        </div>
        <div class="card academy-content shadow-none border">
          <div class="p-2 text-center">
            <img class="img-fluid rounded" src="{{ asset($course['image']) }}" alt="{{ $course['title'] }}" />
          </div>
          <div class="card-body pt-4">
            <h5>Résumé</h5>
            <p class="mb-0">{{ $course['excerpt'] ?: 'Documentation opérationnelle.' }}</p>
            <hr class="my-6" />
            <h5>Contenu</h5>
            <iframe src="{{ route('docs.show', $course['slug']) }}" class="w-100 border rounded" style="min-height:900px;"></iframe>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-4">Informations</h5>
        <p class="text-nowrap mb-2"><i class="icon-base ti tabler-clock me-2 align-bottom"></i>Durée: {{ $course['duration'] }}</p>
        <p class="text-nowrap mb-2"><i class="icon-base ti tabler-layers-intersect me-2 align-bottom"></i>Niveau: {{ $course['level'] }}</p>
        <p class="text-nowrap mb-0"><i class="icon-base ti tabler-star me-2 align-bottom"></i>Note: {{ $course['rating'] }} ({{ $course['rating_count'] }})</p>
        <hr class="my-4" />
        <a href="{{ route('docs.index') }}" class="btn btn-primary w-100">Retour Academy</a>
      </div>
    </div>
  </div>
</div>
@endsection
