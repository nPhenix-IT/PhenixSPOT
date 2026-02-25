<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const routes = document.getElementById('jsRoutes').dataset;
    const editModalEl = document.getElementById('modalEditServer');
    const editModal = new bootstrap.Modal(editModalEl);
    const editForm = document.getElementById('formEditServer');

    // --- HELPER: Appels API ---
    async function apiCall(url, method = 'GET', body = null) {
        const options = {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        };
        if (body) options.body = JSON.stringify(body);
        
        const response = await fetch(url, options);
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Erreur serveur');
        return data;
    }

    // --- DELEGATION D'EVENEMENTS (C'est ici que tes boutons revivent) ---
    // On écoute sur le container parent qui ne change jamais
    document.getElementById('vpnServersList')?.addEventListener('click', async (e) => {
        const btnEdit = e.target.closest('.btn-edit-server');
        const btnDelete = e.target.closest('.btn-delete-server');
        const btnTest = e.target.closest('.btn-test-conn');

        // ACTION: MODIFIER
        if (btnEdit) {
            const id = btnEdit.dataset.id;
            try {
                const url = routes.jsonUrl.replace('__ID__', id);
                const server = await apiCall(url);
                
                // Remplissage du formulaire
                document.getElementById('edit_id').value = server.id;
                document.getElementById('edit_name').value = server.name;
                document.getElementById('edit_server_type').value = server.server_type;
                document.getElementById('edit_is_active').checked = !!server.is_active;

                // Affichage conditionnel des champs
                const isWg = server.server_type === 'wireguard';
                document.getElementById('fields-wireguard').classList.toggle('d-none', !isWg);
                document.getElementById('fields-routeros').classList.toggle('d-none', isWg);

                if(isWg) {
                    document.getElementById('edit_wg_endpoint').value = server.wg_endpoint_address || '';
                    document.getElementById('edit_wg_port').value = server.wg_endpoint_port || '';
                } else {
                    document.getElementById('edit_ip_address').value = server.ip_address || '';
                    document.getElementById('edit_api_port').value = server.api_port || '';
                }

                editModal.show();
            } catch (err) {
                alert("Erreur lors du chargement: " + err.message);
            }
        }

        // ACTION: SUPPRIMER
        if (btnDelete) {
            if (!confirm('Voulez-vous vraiment supprimer ce serveur ?')) return;
            const id = btnDelete.dataset.id;
            try {
                const url = routes.deleteUrl.replace('__ID__', id);
                await apiCall(url, 'DELETE');
                window.location.reload();
            } catch (err) {
                alert(err.message);
            }
        }
    });

    // --- SOUMISSION MODIFICATION ---
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('edit_id').value;
        const formData = new FormData(editForm);
        const data = Object.fromEntries(formData.entries());
        
        // Correction pour le checkbox
        data.is_active = document.getElementById('edit_is_active').checked;

        try {
            const url = routes.updateUrl.replace('__ID__', id);
            await apiCall(url, 'PUT', data);
            
            const alertBox = document.getElementById('editAlert');
            alertBox.className = "alert alert-success";
            alertBox.textContent = "Serveur mis à jour avec succès !";
            alertBox.classList.remove('d-none');
            
            setTimeout(() => window.location.reload(), 800);
        } catch (err) {
            const alertBox = document.getElementById('editAlert');
            alertBox.className = "alert alert-danger";
            alertBox.textContent = err.message;
            alertBox.classList.remove('d-none');
        }
    });
});
</script>