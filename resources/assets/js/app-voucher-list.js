'use strict';
document.addEventListener('DOMContentLoaded', function () {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  var dt_voucher_table = $('.datatables-vouchers');
  var dt_voucher;

  function getSelectedIds() {
    return $('.voucher-checkbox:checked')
      .map(function () {
        return $(this).val();
      })
      .get();
  }

  function toggleActionButtons() {
    const anyChecked = getSelectedIds().length > 0;
    $('#delete-selected-btn').prop('disabled', !anyChecked);
    $('#print-selected-btn').prop('disabled', !anyChecked);
  }

  if (dt_voucher_table.length) {
    dt_voucher = dt_voucher_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/vouchers',
        data: function (d) {
          d.profile_id = $('#profile-filter').val();
        }
      },
      columns: [
        {
          data: 'id',
          name: 'id',
          orderable: false,
          searchable: false,
          render: function (data, type, row) {
            return `<input type="checkbox" class="voucher-checkbox" value="${row.id}">`;
          }
        },
        { data: 'code', name: 'code' },
        { data: 'profile_name', name: 'profile.name' },
        { data: 'status', name: 'status' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
      ],
      pageLength: 10,
      order: [[1, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
    });
    dt_voucher.on('draw', function () {
      $('#select-all-checkbox').prop('checked', false);
      toggleActionButtons();
    });
  }
  $('#generate-vouchers-form').on('submit', function (e) {
    e.preventDefault();
    $.ajax({
      data: $(this).serialize(),
      url: '/vouchers',
      type: 'POST',
      success: function (response) {
        if (dt_voucher) dt_voucher.ajax.reload();
        Swal.fire({ icon: 'success', title: 'Succès!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
      },
      error: function () {
        Swal.fire({ icon: 'error', title: 'Erreur!', text: 'La génération a échoué.' });
      }
    });
  });
  $('#profile-filter').on('change', function () {
    if (dt_voucher) dt_voucher.ajax.reload();
    $('#print-by-profile-btn').prop('disabled', $(this).val() === '');
  });

  dt_voucher_table.on('change', '#select-all-checkbox, .voucher-checkbox', function () {
    if ($(this).is('#select-all-checkbox')) {
      $('.voucher-checkbox').prop('checked', this.checked);
    }
    toggleActionButtons();
  });

  $('#delete-selected-btn').on('click', function () {
    const ids = getSelectedIds();
    if (ids.length === 0) return;

    Swal.fire({
      title: `Supprimer ${ids.length} voucher(s) ?`,
      text: 'Action irréversible.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Oui, supprimer !',
      cancelButtonText: 'Annuler'
    }).then(result => {
      if (!result.isConfirmed) return;
      $.ajax({
        url: '/vouchers/bulk-delete',
        type: 'DELETE',
        data: { ids: ids },
        success: function (response) {
          if (dt_voucher) dt_voucher.ajax.reload(null, false);
          Swal.fire({ icon: 'success', title: 'Supprimé!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        },
        error: function () {
          Swal.fire({ icon: 'error', title: 'Erreur!', text: 'La suppression groupée a échoué.' });
        }
      });
    });
  });

  dt_voucher_table.on('click', '.item-delete', function () {
    const voucherId = $(this).data('id');
    Swal.fire({
      title: 'Êtes-vous sûr ?',
      text: 'Cette action est irréversible.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Oui, supprimer !',
      cancelButtonText: 'Annuler'
    }).then(result => {
      if (!result.isConfirmed) return;
      $.ajax({
        url: '/vouchers/' + voucherId,
        type: 'DELETE',
        success: function (response) {
          if (dt_voucher) dt_voucher.ajax.reload(null, false);
          Swal.fire({ icon: 'success', title: 'Supprimé!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        },
        error: function () {
          Swal.fire({ icon: 'error', title: 'Erreur!', text: 'Impossible de supprimer ce voucher.' });
        }
      });
    });
  });
  
  $('#print-selected-btn, #print-by-profile-btn').on('click', function () {
    let payload;
    let url;
    if ($(this).is('#print-by-profile-btn')) {
      url = '/vouchers/print-by-profile';
      payload = { profile_id: $('#profile-filter').val() };
    } else {
      url = '/vouchers/print';
      payload = { ids: getSelectedIds() };
    }
    if ((payload.ids && payload.ids.length > 0) || payload.profile_id) {
      $.ajax({
        url: url,
        type: 'POST',
        data: payload,
        success: function (response) {
          $('#print-content').html(response.html);
          new bootstrap.Modal(document.getElementById('printVouchersModal')).show();
        },
        error: function () {
          Swal.fire({ icon: 'error', title: 'Erreur!', text: "Impossible d'ouvrir l'aperçu d'impression." });
        }
      });
    }
  });

  $('#launch-print-btn').on('click', function () {
    const printContent = document.getElementById('print-content').innerHTML;

    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    document.body.appendChild(iframe);
    
    const iframeDoc = iframe.contentWindow.document;
    iframeDoc.open();
    iframeDoc.write('<html><head><title>Imprimer</title></head><body>' + printContent + '</body></html>');
    iframeDoc.close();

    iframe.contentWindow.focus();
    iframe.contentWindow.print();

    setTimeout(() => {
      document.body.removeChild(iframe);
    }, 1000);
  });

  const templateModal = document.getElementById('templateEditorModal');
  if (templateModal) {
    let editor;

    const openTemplateModal = templateValue => {
      if (!editor) {
        editor = CodeMirror.fromTextArea(document.getElementById('template-editor'), {
          mode: 'htmlmixed',
          theme: 'dracula',
          lineNumbers: true,
          autoCloseTags: true
        });
      }

      editor.setValue(templateValue || '');
      new bootstrap.Modal(templateModal).show();
    };

    $(document).on('click', '#edit-template-btn', function (e) {
      e.preventDefault();
      $.ajax({
        url: $(this).data('template-url') || '/vouchers/template',
        type: 'GET',
        success: function (data) {
          openTemplateModal(data.template);
        },
        error: function () {
          Swal.fire({ icon: 'error', title: 'Erreur!', text: "Impossible de charger le template." });
        }
      });
    });
    $('#save-template-btn').on('click', function () {
      if (!editor) {
        Swal.fire({ icon: 'warning', title: 'Template non chargé', text: "Ouvrez d'abord l'éditeur de template." });
        return;
      }
      $.ajax({
        url: '/vouchers/template',
        type: 'POST',
        data: { template: editor.getValue() },
        success: function (response) {
          bootstrap.Modal.getInstance(templateModal).hide();
          Swal.fire({ icon: 'success', title: 'Succès!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        },
        error: function () {
          Swal.fire({ icon: 'error', title: 'Erreur!', text: 'La sauvegarde du template a échoué.' });
        }
      });
    });
  }

  toggleActionButtons();
});