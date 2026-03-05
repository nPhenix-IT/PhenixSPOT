'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const table = document.querySelector('#pppoeAccountsTable');
  if (!table || typeof window.jQuery === 'undefined' || !window.jQuery().DataTable) {
    return;
  }

  window.jQuery(table).DataTable({
    processing: true,
    serverSide: true,
    ajax: table.dataset.source,
    pageLength: 10,
    order: [[0, 'asc']],
    columns: [
      { data: 'username', name: 'username' },
      { data: 'router_name', name: 'router.name', defaultContent: '—' },
      { data: 'profile_name', name: 'profile.name', defaultContent: '—' },
      { data: 'ip_address', name: 'ip_address', defaultContent: '—' },
      { data: 'online_badge', name: 'online_badge', orderable: false, searchable: false },
      { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ]
  });
});
