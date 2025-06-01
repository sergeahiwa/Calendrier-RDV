// divi-calendar.js : Gestion FullCalendar + slots dynamiques (Divi 5, WP AJAX sécurisé)
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('rdv-calendar');
    const slotsEl = document.getElementById('rdv-slots');
    let selectedDate = null;
    let prestataire_id = calendarAjax.prestataire_id || 1;
    if (!calendarEl || !slotsEl) {
        console.error('Conteneur calendrier ou slots manquant');
        return;
    }
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        selectable: true,
        height: 420,
        headerToolbar: { left: '', center: 'title', right: '' },
        dateClick: function(info) {
            selectedDate = info.dateStr;
            loadSlots(selectedDate);
        },
        events: function(info, successCallback, failureCallback) {
            // Optionnel : charger les RDV existants pour affichage
            fetch(calendarAjax.ajax_url + '?action=get_rdv_events')
                .then(r => r.json())
                .then(data => successCallback(data))
                .catch(failureCallback);
        }
    });
    calendar.render();

    function loadSlots(dateStr) {
        slotsEl.innerHTML = '<p>Chargement des créneaux...</p>';
        fetch(calendarAjax.ajax_url + '?action=get_slots&nonce=' + calendarAjax.nonce + '&date=' + dateStr + '&prestataire_id=' + prestataire_id)
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    if(result.data.length === 0) {
                        slotsEl.innerHTML = '<p>Aucun créneau disponible ce jour.</p>';
                        return;
                    }
                    slotsEl.innerHTML = result.data.map(time =>
                        `<button class="rdv-slot-btn" data-time="${time}">${time}</button>`
                    ).join('');
                    document.querySelectorAll('.rdv-slot-btn').forEach(btn => {
                        btn.onclick = function() { openBookingModal(dateStr, btn.dataset.time); };
                    });
                } else {
                    slotsEl.innerHTML = '<p>Erreur lors du chargement des créneaux.</p>';
                }
            })
            .catch(() => { slotsEl.innerHTML = '<p>Erreur réseau.</p>'; });
    }

    function openBookingModal(date, time) {
        const form = document.createElement('form');
        form.innerHTML = `
            <h4>Réserver le ${date} à ${time}</h4>
            <label>Nom/Titre <input name="title" required></label><br>
            <label>Email <input type="email" name="email" required></label><br>
            <label>Téléphone <input name="telephone" required></label><br>
            <button type="submit">Confirmer</button>
            <button type="button" id="close-modal">Annuler</button>
        `;
        slotsEl.innerHTML = '';
        slotsEl.appendChild(form);
        form.querySelector('#close-modal').onclick = () => loadSlots(date);
        form.onsubmit = function(e) {
            e.preventDefault();
            const fd = new FormData(form);
            fd.append('action', 'book_slot');
            fd.append('nonce', calendarAjax.nonce);
            fd.append('date', date);
            fd.append('time', time);
            fd.append('prestataire_id', prestataire_id);
            fetch(calendarAjax.ajax_url, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(result => {
                    if(result.success) {
                        alert(result.data.message);
                        calendar.refetchEvents && calendar.refetchEvents();
                        loadSlots(date);
                    } else {
                        alert(result.data.message || 'Erreur lors de la réservation.');
                    }
                })
                .catch(() => alert('Erreur réseau.'));
        };
    }
});
