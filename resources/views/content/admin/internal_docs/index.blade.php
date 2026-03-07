@extends('layouts/layoutMaster')

@section('title', 'Docs internes')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1">Documentation interne (fichiers Blade)</h4>
      <p class="text-muted mb-0">Gérez les pages docs générées en <code>resources/views/content/internal-docs/custom/*.blade.php</code>.</p>
    </div>
    <a href="{{ route('admin.internal-docs.create') }}" class="btn btn-primary">Nouvelle page</a>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Titre</th>
            <th>Slug</th>
            <th>Statut</th>
            <th>Mise à jour</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pages as $page)
            <tr>
              <td class="fw-semibold">{{ $page['title'] }}</td>
              <td><code>{{ $page['slug'] }}</code></td>
              <td>
                @if(!empty($page['is_published']))
                  <span class="badge bg-label-success">Publié</span>
                @else
                  <span class="badge bg-label-warning">Brouillon</span>
                @endif
              </td>
              <td>{{ $page['updated_at'] ?? '-' }}</td>
              <td class="text-end">
                <a href="{{ route('docs.show', $page['slug']) }}" class="btn btn-sm btn-label-primary">Voir</a>
                <a href="{{ route('admin.internal-docs.edit', $page['slug']) }}" class="btn btn-sm btn-primary">Éditer</a>
                <form action="{{ route('admin.internal-docs.destroy', $page['slug']) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette page ?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-label-danger">Supprimer</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4">Aucune page pour le moment.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
