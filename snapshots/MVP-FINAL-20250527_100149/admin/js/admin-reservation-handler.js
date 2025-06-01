// admin/js/admin-reservation-handler.js
// Centralise les actions sur les RDV : annulation, édition (future), duplication (future)

window.calrdvReservationHandler = {
    onEdit: function(reservation) {
        alert('Edition à venir pour le RDV #' + reservation.id);
    }
};
