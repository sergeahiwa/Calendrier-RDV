# Documentation Technique

## Architecture
- `src/` : Code source principal
  - `Infrastructure/` : Services externes (paiements, export)
  - `Domain/` : Logique métier
  - `Application/` : Cas d'utilisation

## Tests
```bash
# Lancer les tests unitaires
vendor/bin/phpunit --testsuite Unit

# Générer un rapport de couverture
vendor/bin/phpunit --coverage-html coverage
```

## Services
### ExportService
Génère des exports CSV/Excel des rendez-vous.

### Services de paiement
- `AppleGooglePayService`
- `MobileMoneyService`

## Hooks et filtres
Voir `includes/class-hooks.php` pour la liste complète.
