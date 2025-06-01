<?php
// admin/admin-calendrier.php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('Accès refusé.', 'calendrier-rdv'));
$page_title = __('Calendrier des rendez-vous', 'calendrier-rdv');
?>
<div class="wrap">
  <h1><?php echo esc_html($page_title); ?></h1>
  <label for="calrdv-filtre-presta"><b>Filtrer par prestataire :</b></label>
<select id="calrdv-filtre-presta"><option value="">Tous les prestataires</option></select>
<div id="calrdv-admin-calendar"></div>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<style>
#calrdv-admin-calendar { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #ccc; padding: 20px; }
</style>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<?php
// Localise les données JS pour sécuriser l’API
wp_enqueue_script('calrdv-admin-calendar', plugins_url('../admin/js/admin-calendar.js', __FILE__), ['jquery'], '1.0', true);
wp_localize_script('calrdv-admin-calendar', 'calRDVAdminData', [
    'rest_url' => esc_url_raw(rest_url('calrdv/v1/')),
    'nonce' => wp_create_nonce('calrdv_cancel_rdv'),
]);
?>
