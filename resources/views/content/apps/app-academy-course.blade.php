@extends('layouts/layoutMaster')
@php
$configData = Helper::appClasses();
@endphp

@section('title', 'My Courses - Academy')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/plyr/plyr.scss'])
@endsection

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-academy.scss')
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/plyr/plyr.js'])
@endsection

@section('page-script')
@vite('resources/assets/js/app-academy-course.js')
@endsection

@section('content')
<div class="app-academy">
  <div class="card p-0 mb-6">
    <div class="card-body d-flex flex-column flex-md-row justify-content-between p-0 pt-6">
      <div class="app-academy-md-25 card-body py-0 pt-6 ps-12">
        <img src="{{ asset('assets/img/illustrations/bulb-' . $configData['theme'] . '.png') }}" class="img-fluid app-academy-img-height scaleX-n1-rtl" alt="Bulb" data-app-light-img="illustrations/bulb-light.png" data-app-dark-img="illustrations/bulb-dark.png" height="90" />
      </div>
      <div class="app-academy-md-50 card-body d-flex align-items-md-center flex-column text-md-center mb-6 py-6">
        <span class="card-title mb-4 lh-lg px-md-12 h4 text-heading">Documentation <span class="text-primary text-nowrap">PhenixSPOT</span></span>
        <p class="mb-4">Retrouvez tous les guides de configuration et d’exploitation, dans une interface type Academy.</p>
      </div>
      <div class="app-academy-md-25 d-flex align-items-end justify-content-end">
        <img src="{{ asset('assets/img/illustrations/pencil-rocket.png') }}" alt="pencil rocket" height="188" class="scaleX-n1-rtl" />
      </div>
    </div>
  </div>

  <div class="card mb-6">
    <div class="card-header d-flex flex-wrap justify-content-between gap-4">
      <div class="card-title mb-0 me-1">
        <h5 class="mb-0">Mes documentations</h5>
        <p class="mb-0">Total {{ $courses->count() }} documentation(s) publiée(s)</p>
      </div>
    </div>
    <div class="card-body">
      <div class="row gy-6 mb-6">
        @forelse($courses as $course)
          <div class="col-sm-6 col-lg-4">
            <div class="card p-2 h-100 shadow-none border">
              <div class="rounded-2 text-center mb-4">
                <a href="{{ route('docs.show', $course['slug']) }}"><img class="img-fluid" src="{{ asset($course['image']) }}" alt="{{ $course['title'] }}" /></a>
              </div>
              <div class="card-body p-4 pt-2">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <span class="badge bg-label-primary">{{ $course['category'] }}</span>
                  <p class="d-flex align-items-center justify-content-center fw-medium gap-1 mb-0">{{ $course['rating'] }} <span class="text-warning"><i class="icon-base ti tabler-star-filled icon-lg me-1 mb-1_5"></i></span><span class="fw-normal">({{ $course['rating_count'] }})</span></p>
                </div>
                <a href="{{ route('docs.show', $course['slug']) }}" class="h5">{{ $course['title'] }}</a>
                <p class="mt-1">{{ $course['excerpt'] ?: 'Consultez ce guide de documentation.' }}</p>
                <p class="d-flex align-items-center mb-1"><i class="icon-base ti tabler-clock me-1"></i>{{ $course['duration'] }}</p>
                <div class="d-flex flex-column flex-md-row gap-4 text-nowrap">
                  <a class="w-100 btn btn-label-primary d-flex align-items-center" href="{{ route('docs.show', $course['slug']) }}"> <span class="me-2">Lire</span><i class="icon-base ti tabler-chevron-right icon-xs lh-1 scaleX-n1-rtl"></i> </a>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <div class="alert alert-info mb-0">Aucune documentation publiée pour le moment.</div>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
