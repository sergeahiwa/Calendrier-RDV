# Composant Calendrier Réutilisable

## Installation
1. Copier le dossier `/components/calendar/` dans votre projet.
2. Adapter `calendar.config.php` selon vos besoins.
3. Inclure le CSS et le JS dans vos pages.

## Intégration rapide
```php
<?php
$calendarConfig = require __DIR__ . '/components/calendar/calendar.config.php';
?>
<link rel="stylesheet" href="/components/calendar/calendar.css">
<div id="calendar-container" data-config='<?= json_encode($calendarConfig) ?>'></div>
<script src="/components/calendar/calendar.js"></script>
<script>
window.calendarCustomHandlers = {
  eventClick: function(info) {
    alert('Événement personnalisé : ' + info.event.title);
  }
};
</script>
```

## Personnalisation
- Modifiez `calendar.config.php` pour changer les couleurs, labels, endpoints...
- Ajoutez vos propres callbacks JS via `window.calendarCustomHandlers`.

## Endpoints AJAX
- Voir `calendar.endpoints.php` pour adapter la connexion à votre base de données.
