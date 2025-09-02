'use strict';

$(function () {
  // Datatable for Projects
  var dt_project_table = $('.datatable-project');
  if (dt_project_table.length) {
    dt_project_table.DataTable({
      ajax: '/assets/json/user-profile.json', // Remplacez par une route réelle si nécessaire
      columns: [
        { data: '' },
        { data: 'id' },
        { data: 'project_name' },
        { data: 'leader' },
        { data: 'team' },
        { data: 'status' },
        { data: 'action' }
      ],
      // ... (autres configurations de DataTable si nécessaire)
    });
  }
});