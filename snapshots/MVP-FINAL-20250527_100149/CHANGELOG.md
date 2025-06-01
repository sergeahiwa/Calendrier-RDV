# Changelog

## [2025-05-26] - v1.4.0
### Nouveautés
- **Système de file d'attente** pour les échecs d'envoi d'emails
- **Template HTML** pour les emails de confirmation de rendez-vous
- **Gestion des erreurs** améliorée avec journalisation détaillée
- **Tâches planifiées** pour le retry automatique des envois échoués

### Améliorations
- Meilleure gestion des erreurs temporaires de l'API MailerSend
- Backoff exponentiel pour les tentatives d'envoi
- Nettoyage automatique des anciens échecs
- Documentation technique mise à jour

## [2025-05-12] - v1.3.1
- Ajout de la protection .htaccess dans admin/, includes/, rdv-handler/
- Ajout du système de logs (logger.php + logs/events.log)
- Export CSV des rendez-vous (admin/export-csv.php)
- Sécurisation et refonte complète du traitement AJAX FullCalendar
