'use strict';

$(function () {
  // Variable pour la Datatable
  var dt_router_table = $('.datatables-routers');

  // Configuration de la Datatable
  if (dt_router_table.length) {
    var dt_router = dt_router_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '{{ route('user.routers.index') }}'
      },
      columns: [
        { data: 'id' }, // Colonne vide pour les contrôles responsives de Datatable
        { data: 'name', name: 'name' },
        { data: 'ip_address', name: 'ip_address' },
        { data: 'location', name: 'location' },
        { data: 'status', name: 'status' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
      ],
      columnDefs: [
        {
          // Pour les contrôles responsives
          className: 'control',
          orderable: false,
          searchable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        }
      ],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Détails de ' + data['name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== ''
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });
  }

  // Gestion du formulaire d'ajout/modification
  const addNewRouterForm = document.getElementById('addNewRouterForm');
  const fv = FormValidation.formValidation(addNewRouterForm, {
    fields: {
      name: { validators: { notEmpty: { message: 'Le nom est requis' } } },
      ip_address: { validators: { notEmpty: { message: "L'adresse IP est requise" }, ip: { message: "Veuillez entrer une adresse IP valide" } } },
      radius_secret: { validators: { notEmpty: { message: 'Le secret RADIUS est requis' }, stringLength: {min: 6, message: 'Le secret doit contenir au moins 6 caractères'} } }
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({ eleValidClass: '', rowSelector: '.mb-3' }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  });

  // Clic sur le bouton "Ajouter"
  $('#add-new-router-btn').on('click', function() {
    $('#router_id').val(''); // Vider l'ID
    $('#offcanvasAddRouterLabel').html('Ajouter un Routeur');
    const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasAddRouter'));
    offcanvas.show();
    addNewRouterForm.reset();
  });

  // Soumission du formulaire
  fv.on('core.form.valid', function () {
    $.ajax({
      data: $('#addNewRouterForm').serialize(),
      url: '{{ route('user.routers.store') }}',
      type: 'POST',
      success: function (response) {
        dt_router.ajax.reload();
        bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAddRouter')).hide();
        Swal.fire({
          icon: 'success',
          title: 'Succès!',
          text: response.success,
          customClass: { confirmButton: 'btn btn-success' }
        });
      },
      error: function (err) {
        Swal.fire({
          icon: 'error',
          title: 'Erreur!',
          text: err.responseJSON.error || 'Une erreur est survenue.',
          customClass: { confirmButton: 'btn btn-danger' }
        });
      }
    });
  });

  // Édition d'un enregistrement
  $(document).on('click', '.item-edit', function () {
    var router_id = $(this).data('id');
    $('#offcanvasAddRouterLabel').html('Modifier le Routeur');
    const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasAddRouter'));
    offcanvas.show();

    $.get('{{ url('user/routers') }}/' + router_id + '/edit', function (data) {
        $('#router_id').val(data.id);
        $('#router-name').val(data.name);
        $('#router-ip').val(data.ip_address);
        $('#router-secret').val(data.radius_secret);
        $('#router-model').val(data.model);
        $('#router-location').val(data.location);
    });
  });

  // Suppression d'un enregistrement
  $(document).on('click', '.item-delete', function () {
    var router_id = $(this).data('id');
    Swal.fire({
      title: 'Êtes-vous sûr ?',
      text: "Vous ne pourrez pas revenir en arrière !",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Oui, supprimer !',
      cancelButtonText: 'Annuler',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.isConfirmed) {
        $.ajax({
          type: "DELETE",
          url: '{{ url('user/routers') }}/' + router_id,
          data: {
              "_token": "{{ csrf_token() }}",
          },
          success: function (data) {
            dt_router.ajax.reload();
            Swal.fire({
              icon: 'success',
              title: 'Supprimé!',
              text: data.success,
              customClass: { confirmButton: 'btn btn-success' }
            });
          },
          error: function (data) {
            Swal.fire({
              icon: 'error',
              title: 'Erreur!',
              text: 'La suppression a échoué.',
              customClass: { confirmButton: 'btn btn-danger' }
            });
          }
        });
      }
    });
  });
});