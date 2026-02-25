<script>
document.addEventListener('DOMContentLoaded', function () {
    const bootstrapAvailable = typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const routes = document.getElementById('jsRoutes').dataset;
    const editModalEl = document.getElementById('modalEditServer');
    const editModal = bootstrapAvailable && editModalEl ? window.bootstrap.Modal.getOrCreateInstance(editModalEl) : null;
    const editForm = document.getElementById('formEditServer');
    const createRouterForm = document.getElementById('formCreateRouterOs');
    const createWgForm = document.getElementById('formCreateWireGuard');
    const createRouterModalEl = document.getElementById('modalCreateRouterOs');
    const createWgModalEl = document.getElementById('modalCreateWireGuard');
    const createRouterModal = bootstrapAvailable && createRouterModalEl ? window.bootstrap.Modal.getOrCreateInstance(createRouterModalEl) : null;
    const createWgModal = bootstrapAvailable && createWgModalEl ? window.bootstrap.Modal.getOrCreateInstance(createWgModalEl) : null;

    function openModalFallback(modalEl) {
        if (!modalEl) return;
        modalEl.style.display = 'block';
        modalEl.classList.add('show');
        modalEl.removeAttribute('aria-hidden');
        modalEl.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open');
    }

    function closeModalFallback(modalEl) {
        if (!modalEl) return;
        modalEl.style.display = 'none';
        modalEl.classList.remove('show');
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.removeAttribute('aria-modal');
        document.body.classList.remove('modal-open');
    }

    function notify(type, message, title = '') {
        if (typeof window.Swal !== 'undefined') {
            window.Swal.fire({
                icon: type,
                title: title || (type === 'success' ? 'Succès' : type === 'error' ? 'Erreur' : 'Information'),
                text: message,
                confirmButtonText: 'OK'
            });
            return;
        }

        // fallback minimal
        if (type === 'error') {
            console.error(message);
        }
        alert(message);
    }

    async function askConfirm(message) {
        if (typeof window.Swal !== 'undefined') {
            const result = await window.Swal.fire({
                icon: 'warning',
                title: 'Confirmation',
                text: message,
                showCancelButton: true,
                confirmButtonText: 'Oui',
                cancelButtonText: 'Annuler'
            });
            return result.isConfirmed;
        }

        return confirm(message);
    }

    document.getElementById('btnOpenRouterModal')?.addEventListener('click', function (e) {
        if (createRouterModal) {
            e.preventDefault();
            createRouterModal.show();
        } else if (!bootstrapAvailable) {
            e.preventDefault();
            openModalFallback(createRouterModalEl);
        }
    });

    document.getElementById('btnOpenWireguardModal')?.addEventListener('click', function (e) {
        if (createWgModal) {
            e.preventDefault();
            createWgModal.show();
        } else if (!bootstrapAvailable) {
            e.preventDefault();
            openModalFallback(createWgModalEl);
        }
    });

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
                    document.getElementById('edit_name_wg').value = server.name || '';
                    document.getElementById('edit_wg_endpoint').value = server.wg_endpoint_address || server.domain_name || server.ip_address || server.host || '';
                    document.getElementById('edit_wg_port').value = server.wg_endpoint_port || '';
                    document.getElementById('edit_wg_interface').value = server.wg_interface || '';
                    document.getElementById('edit_wg_public_key').value = server.wg_server_public_key || '';
                    document.getElementById('edit_wg_private_key').value = '';
                    document.getElementById('edit_wg_network').value = server.wg_network || server.ip_range || '';
                    document.getElementById('edit_wg_server_address').value = server.wg_server_address || server.local_ip_address || '';
                    document.getElementById('edit_wg_dns').value = server.wg_dns || '';
                    document.getElementById('edit_wg_mtu').value = server.wg_mtu || '';
                    document.getElementById('edit_wg_keepalive').value = server.wg_persistent_keepalive || '';
                    document.getElementById('edit_max_accounts_wg').value = server.max_accounts || server.account_limit || '';
                    document.getElementById('edit_location_wg').value = server.location || '';
                } else {
                    document.getElementById('edit_ip_address').value = server.ip_address || server.host || '';
                    document.getElementById('edit_api_port').value = server.api_port || '';
                    document.getElementById('edit_api_user').value = server.api_user || '';
                    document.getElementById('edit_api_password').value = '';
                    document.getElementById('edit_profile_name').value = server.profile_name || 'default';
                    document.getElementById('edit_gateway_ip').value = server.gateway_ip || server.local_ip_address || '';
                    document.getElementById('edit_domain_name').value = server.domain_name || '';
                    document.getElementById('edit_ip_pool').value = server.ip_pool || server.ip_range || '';
                    document.getElementById('edit_max_accounts_router').value = server.max_accounts || server.account_limit || '';
                    document.getElementById('edit_location_router').value = server.location || '';
                }

                if (editModal) {
                    editModal.show();
                } else {
                    openModalFallback(editModalEl);
                }
            } catch (err) {
                notify('error', "Erreur lors du chargement: " + err.message);
            }
        }

        // ACTION: SUPPRIMER
        if (btnDelete) {
            const confirmed = await askConfirm('Voulez-vous vraiment supprimer ce serveur ?');
            if (!confirmed) return;
            const id = btnDelete.dataset.id;
            try {
                const url = routes.deleteUrl.replace('__ID__', id);
                await apiCall(url, 'DELETE');
                window.location.reload();
            } catch (err) {
                notify('error', err.message);
            }
        }

        // ACTION: TEST CONNEXION
        if (btnTest) {
            const id = btnTest.dataset.id;
            const oldHtml = btnTest.innerHTML;
            btnTest.disabled = true;
            btnTest.innerHTML = '<i class="ti tabler-loader-2 me-1"></i> Test...';

            try {
                const result = await apiCall(routes.testUrl, 'POST', { server_id: id });
                notify('success', result.message || 'Test de connexion réussi');
                window.location.reload();
            } catch (err) {
                notify('error', err.message || 'Échec du test de connexion');
            } finally {
                btnTest.disabled = false;
                btnTest.innerHTML = oldHtml;
            }
        }
    });

    // --- SOUMISSION MODIFICATION ---
    editForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('edit_id').value;
        const serverType = document.getElementById('edit_server_type').value;
        const formData = new FormData(editForm);
        const data = Object.fromEntries(formData.entries());
        
        // Correction pour le checkbox
        data.is_active = document.getElementById('edit_is_active').checked;
    
        // Harmonisation des champs communs selon la section active
        if (serverType === 'wireguard') {
            data.name = document.getElementById('edit_name_wg')?.value || data.name;
            data.max_accounts = document.getElementById('edit_max_accounts_wg')?.value || '';
            data.location = document.getElementById('edit_location_wg')?.value || '';
        } else {
            data.max_accounts = document.getElementById('edit_max_accounts_router')?.value || '';
            data.location = document.getElementById('edit_location_router')?.value || '';
        }
    
        delete data.max_accounts_router;
        delete data.max_accounts_wg;
        delete data.location_router;
        delete data.location_wg;
        delete data.name_wg;
    
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

    // --- SOUMISSION CREATION RouterOS ---
    createRouterForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(createRouterForm);
        const data = Object.fromEntries(formData.entries());
        data.is_active = document.getElementById('create_routeros_is_active')?.checked ?? true;

        const alertBox = document.getElementById('createRouterAlert');
        try {
            await apiCall(routes.storeUrl, 'POST', data);
            alertBox.className = 'alert alert-success';
            alertBox.textContent = 'Serveur MikroTik CHR ajouté avec succès.';
            alertBox.classList.remove('d-none');
            setTimeout(() => {
                if (createRouterModal) {
                    createRouterModal.hide();
                } else {
                    closeModalFallback(createRouterModalEl);
                }
                window.location.reload();
            }, 700);
        } catch (err) {
            alertBox.className = 'alert alert-danger';
            alertBox.textContent = err.message;
            alertBox.classList.remove('d-none');
        }
    });

    // --- SOUMISSION CREATION WireGuard ---
    createWgForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(createWgForm);
        const data = Object.fromEntries(formData.entries());
        data.is_active = document.getElementById('create_wg_is_active')?.checked ?? true;

        const alertBox = document.getElementById('createWireguardAlert');
        try {
            await apiCall(routes.storeUrl, 'POST', data);
            alertBox.className = 'alert alert-success';
            alertBox.textContent = 'Serveur WireGuard ajouté avec succès.';
            alertBox.classList.remove('d-none');
            setTimeout(() => {
                if (createWgModal) {
                    createWgModal.hide();
                } else {
                    closeModalFallback(createWgModalEl);
                }
                window.location.reload();
            }, 700);
        } catch (err) {
            alertBox.className = 'alert alert-danger';
            alertBox.textContent = err.message;
            alertBox.classList.remove('d-none');
        }
    });
});
</script>