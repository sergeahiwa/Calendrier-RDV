# ✅ Sécurisation des secrets – Rapport d’intervention technique (Cascade)

## 🔍 Analyse initiale

* **Outil utilisé** : [Gitleaks 8.x](https://github.com/gitleaks/gitleaks) (format JSON : tableau d’objets).
* **Environnement** : Windows local (`c:\Users\HP\calendrier-rdv`).
* **Script de traitement** : `scripts/dev/remove_secrets.py`

## 📊 Résultat du scan

* **27 secrets détectés** dans des fichiers PHP, JS, .env et autres.
* Tous automatiquement commentés par le script de suppression (`# ⚠️ Secret détecté automatiquement par Gitleaks et supprimé.`).

## 🛠️ Étapes techniques réalisées

1. ✅ Adaptation du script Python `remove_secrets.py` au format JSON de Gitleaks 8.x (liste et non objet).
2. ✅ Exécution automatique du script → toutes les lignes exposant un secret ont été commentées.
3. ✅ Vérification manuelle post-correction : **aucune ligne de secret exposée restante**.
4. ✅ Nettoyage : suppression de commentaires de détection une fois les secrets remplacés.
5. ✅ Validation croisée dans tous les fichiers sensibles : `.php`, `.ts`, `.env`, `config/`, etc.
6. ✅ Documentation de la procédure dans le README et le CHANGELOG (trace sécurité).

## 🔐 Standard appliqué pour remplacement

> Tous les secrets exposés ont été remplacés par des appels à des variables d’environnement de type `getenv('NOM_DU_SECRET')` (en PHP), ou des équivalents selon le langage concerné.

Exemple PHP :

```php
// Ancien : $api_key = "sk-abc123xyz";
// Nouveau :
$api_key = getenv('API_KEY');
```

## 📌 Prochaines recommandations

* **Stockage des secrets** : privilégier `.env.local`, `vault`, ou GitHub Secrets pour CI/CD.
* **CI sécurisée** : intégrer un scan Gitleaks régulier dans GitHub Actions (PR & push).
* **Formation** : rappeler aux devs de ne jamais commit des clés en clair.
