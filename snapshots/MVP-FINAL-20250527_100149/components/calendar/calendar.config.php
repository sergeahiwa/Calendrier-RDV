<?php
return [
    'defaultView' => 'dayGridMonth',
    'eventColors' => [
        'confirmé' => '#28a745',
        'en_attente' => '#ffc107',
        'annulé' => '#dc3545'
    ],
    'ajaxEndpoints' => [
        'fetch' => '/components/calendar/calendar.endpoints.php?action=fetch',
        'edit'  => '/components/calendar/calendar.endpoints.php?action=edit',
        'delete'=> '/components/calendar/calendar.endpoints.php?action=delete'
    ],
    'labels' => [
        'addEvent' => 'Ajouter un rendez-vous',
        'editEvent' => 'Modifier',
        'deleteEvent' => 'Supprimer',
        'eventDetails' => 'Détails du rendez-vous'
    ]
];
