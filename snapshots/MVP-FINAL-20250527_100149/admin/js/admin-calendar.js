// admin/js/admin-calendar.js
// Gère l’affichage FullCalendar et l’annulation via REST

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calrdv-admin-calendar');
    if (!calendarEl) return;
    // Remplit le select prestataire dynamiquement
fetch(calRDVAdminData.rest_url + 'prestataires')
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('calrdv-filtre-presta');
            data.prestataires.forEach(presta => {
                const opt = document.createElement('option');
                opt.value = presta.id;
                opt.textContent = presta.nom;
                select.appendChild(opt);
            });
        }
    });

var currentPrestaId = '';
var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            let url = calRDVAdminData.rest_url + 'slots';
            if (currentPrestaId) url += '?prestataire_id=' + encodeURIComponent(currentPrestaId);
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) return failureCallback('Erreur API');
                    const events = data.slots.map(slot => ({
                        id: slot.id,
                        title: slot.nom ? slot.nom + ' - ' + slot.prestation : slot.prestation,
                        start: slot.date_rdv + 'T' + slot.heure_rdv,
                        extendedProps: slot,
                        data: { reservationId: slot.id }
                    }));
                    successCallback(events);
                })
                .catch(err => failureCallback(err));
        },
        eventClick: function(info) {
            // Ajoute data-reservation-id
            info.el.setAttribute('data-reservation-id', info.event.id);
            const props = info.event.extendedProps;
            let html = `<strong>${props.prestation}</strong><br>`;
            html += `<b>Client:</b> ${props.nom || ''}<br>`;
            html += `<b>Email:</b> ${props.email || ''}<br>`;
            html += `<b>Prestataire:</b> ${props.prestataire || ''}<br>`;
            html += `<b>Statut:</b> ${props.statut || ''}<br>`;
            html += `<textarea id='calrdv-motif' placeholder='Motif annulation (optionnel)' style='width:100%;margin-top:8px;'></textarea><br>`;
            html += `<button id='calrdv-annuler-btn'>Annuler ce RDV</button>`;
            if (props.statut === 'en_attente') {
                html += ` <button id='calrdv-confirmer-btn'>Confirmer</button>`;
            }
            html += ` <button id='calrdv-dupliquer-btn'>Dupliquer</button>`;
            html += `<hr><div id='calrdv-historique-zone'><em>Chargement de l’historique...</em></div>`;

// --- Formulaire d’édition inline ---
html += `<hr><div id='calrdv-edit-zone' style='margin-top:12px;'><b>Édition rapide</b><form id='calrdv-edit-form'>
<label>Date : <input type='date' name='date_rdv' value='${props.date_rdv}' required></label><br>
<label>Heure : <input type='time' name='heure_rdv' value='${props.heure_rdv}' required></label><br>
<label>Prestataire : <select name='prestataire' id='calrdv-edit-presta'></select></label><br>
<label>Statut : <select name='statut'>
<option value='en_attente' ${props.statut==='en_attente'?'selected':''}>En attente</option>
<option value='confirme' ${props.statut==='confirme'?'selected':''}>Confirmé</option>
<option value='annule' ${props.statut==='annule'?'selected':''}>Annulé</option>
</select></label><br>
<label>Email : <input type='email' name='email' value='${props.email||''}' required></label><br>
<label>Nom : <input type='text' name='nom' value='${props.nom||''}'></label><br>
<button type='submit' id='calrdv-edit-save'>Enregistrer</button>
<span id='calrdv-edit-msg' style='margin-left:10px;color:#007;'> </span>
</form></div>`;
            const modal = document.createElement('div');
            modal.id = 'calrdv-modal';
            modal.style.position = 'fixed';
            modal.style.left = '0';
            modal.style.top = '0';
            modal.style.width = '100vw';
            modal.style.height = '100vh';
            modal.style.background = 'rgba(0,0,0,0.4)';
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.innerHTML = `<div style='background:#fff;padding:24px;border-radius:8px;max-width:400px;'>${html}<br><button id='calrdv-close-modal'>Fermer</button></div>`;
            document.body.appendChild(modal);
            const closeBtn = document.getElementById('calrdv-close-modal');
            closeBtn.focus();

            // Remplissage dynamique du select prestataire pour le formulaire d’édition
            fetch(calRDVAdminData.rest_url + 'prestataires')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('calrdv-edit-presta');
                        data.prestataires.forEach(presta => {
                            const opt = document.createElement('option');
                            opt.value = presta.id;
                            opt.textContent = presta.nom;
                            if (presta.id == props.prestataire) opt.selected = true;
                            select.appendChild(opt);
                        });
                    const zone = document.getElementById('calrdv-historique-zone');
                    if (!data.success || !Array.isArray(data.logs) || data.logs.length === 0) {
                        zone.innerHTML = '<em>Aucune action enregistrée.</em>';
                        return;
                    }
                    let html = '<b>Historique des actions</b><ul style="max-height:120px;overflow:auto;padding-left:16px;">';
                    const logs = data.logs;
                    const maxLogs = 5;
                    logs.slice(0, maxLogs).forEach(log => {
                        html += `<li><b>${log.timestamp.split(' ')[0]}</b> — <span style='color:#555'>${log.action}</span> par <i>${log.user_name||'?'}</i><br><span style='font-size:0.95em;color:#888'>${log.details||''}</span></li>`;
                    });
                    html += '</ul>';
                    if (logs.length > maxLogs) {
                        html += `<button id='calrdv-historique-plus'>Voir plus</button>`;
                    }
                    zone.innerHTML = html;
                    if (logs.length > maxLogs) {
                        document.getElementById('calrdv-historique-plus').onclick = () => {
                            let full = '<b>Historique complet</b><ul style="max-height:250px;overflow:auto;padding-left:16px;">';
                            logs.forEach(log => {
                                full += `<li><b>${log.timestamp.split(' ')[0]}</b> — <span style='color:#555'>${log.action}</span> par <i>${log.user_name||'?'}</i><br><span style='font-size:0.95em;color:#888'>${log.details||''}</span></li>`;
                            });
                            full += '</ul>';
                            zone.innerHTML = full;
                        };
                    }
                })
                .catch(() => {
                    const zone = document.getElementById('calrdv-historique-zone');
                    if(zone) zone.innerHTML = '<em>Erreur lors du chargement des logs.</em>';
                });

            closeBtn.onclick = () => modal.remove();
            document.getElementById('calrdv-annuler-btn').onclick = function() {
                if (!confirm('Confirmer l’annulation de ce rendez-vous ?')) return;
                const motif = document.getElementById('calrdv-motif').value;
                fetch(calRDVAdminData.rest_url + 'cancel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: props.id,
                        motif,
                        token: calRDVAdminData.nonce
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('RDV annulé. Un email de confirmation a été envoyé au client.');
                        modal.remove();
                        calendar.refetchEvents();
                    } else {
                        alert('Erreur : ' + (data.message || '')); 
                    }
                })
                .catch(err => alert('Erreur : ' + err));
            };
        }
    });
    calendar.render();
});
