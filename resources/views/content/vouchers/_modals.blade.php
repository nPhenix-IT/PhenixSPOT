<!-- Modal pour l'éditeur de template -->
<div class="modal fade" id="templateEditorModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Éditeur de Template de Coupon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8"><textarea id="template-editor"></textarea></div>
                    <div class="col-md-4">
                        <h6>Variables Disponibles</h6>
                        <ul class="list-group">
                            <li class="list-group-item"><strong>@@{{ code }}</strong>: Le code du voucher</li>
                            <li class="list-group-item"><strong>@@{{ profile_name }}</strong>: Nom du profil</li>
                            <li class="list-group-item"><strong>@@{{ price }}</strong>: Prix du voucher</li>
                            <li class="list-group-item"><strong>@@{{ validity }}</strong>: Durée de validité</li>
                            <li class="list-group-item"><strong>@@{{ data_limit }}</strong>: Quota de données</li>
                            <li class="list-group-item"><strong>@@{{ contact }}</strong>: N° de Telephone</li>
                            <li class="list-group-item"><strong>@@{{ qrcode }}</strong>: Le QR Code de connexion</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="save-template-btn">Sauvegarder</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour l'impression -->
<div class="modal fade" id="printVouchersModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Imprimer les Vouchers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body printable-area">
                <div id="print-content">
                    <!-- Le contenu des vouchers sera injecté ici -->
                </div>
            </div><br>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="launch-print-btn">Lancer l'impression</button>
            </div>
        </div>
    </div>
</div>
