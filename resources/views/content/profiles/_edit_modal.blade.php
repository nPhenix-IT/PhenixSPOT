<div class="modal fade" data-bs-backdrop="static" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProfileForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">@include('content.profiles._form_fields')</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>