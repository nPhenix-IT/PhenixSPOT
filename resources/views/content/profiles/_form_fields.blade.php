<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Nom du profil <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" placeholder="Ex: Pass Journée" required></div>
    <div class="col-md-6 mb-3"><label class="form-label">Prix de vente <span class="text-danger">*</span></label><input type="number" name="price" class="form-control" placeholder="Mettre 0 pour Gratuit" required></div>
</div>
<div class="mb-3">
    <label class="form-label">Type de limitation <span class="text-danger">*</span></label>
    <div class="d-flex gap-3">
        <div class="form-check"><input class="form-check-input" type="radio" name="limit_type" value="both" checked><label class="form-check-label">Les Deux</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="limit_type" value="time"><label class="form-check-label">Temps</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="limit_type" value="data"><label class="form-check-label">Données</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="limit_type" value="unlimited"><label class="form-check-label">Illimité</label></div>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Débit (Upload/Download)</label><input type="text" name="rate_limit" class="form-control" placeholder="Ex: 512k/2M"></div>
    <div class="col-md-6 mb-3"><label class="form-label">Nbre d'appareils <span class="text-danger">*</span></label><input type="number" name="device_limit" class="form-control" value="1" min="1" required></div>
</div>
<div class="row form-group-time">
    <div class="col-md-12 mb-3"><label class="form-label">Durée de la session <span class="text-danger required-star">*</span></label><div class="input-group"><input type="number" name="session_duration" class="form-control" placeholder="Ex: 24"><select name="session_unit" class="form-select"><option value="hours">Heure(s)</option><option value="days">Jour(s)</option><option value="weeks">Semaine(s)</option><option value="months">Mois</option></select></div></div>
</div>
<div class="row form-group-data">
    <div class="col-md-12 mb-3"><label class="form-label">Quota de données <span class="text-danger required-star">*</span></label><div class="input-group"><input type="number" name="data_limit_value" class="form-control" placeholder="Ex: 5"><select name="data_unit" class="form-select"><option value="mb">Mo</option><option value="gb">Go</option></select></div></div>
</div>
<div class="row">
    <div class="col-md-12 mb-3"><label class="form-label">Validité du coupon <span class="text-danger">*</span></label><div class="input-group"><input type="number" name="validity_duration" class="form-control" placeholder="Ex: 30" required><select name="validity_unit" class="form-select"><option value="hours">Heure(s)</option><option value="days">Jour(s)</option><option value="weeks">Semaine(s)</option><option value="months">Mois</option></select></div></div>
</div>