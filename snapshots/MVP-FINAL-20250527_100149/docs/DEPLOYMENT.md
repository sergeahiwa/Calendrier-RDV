# Guide de Déploiement

## Environnement Requis
- Serveur web (Apache/Nginx)
- PHP 8.0+
- MySQL 5.7+
- WordPress 5.8+

## Étapes de Déploiement

### 1. Préparation
```bash
# Cloner le dépôt
git clone [url-du-repo]
cd calendrier-rdv

# Installer les dépendances
composer install --no-dev
```

### 2. Configuration
1. Copiez `.env.example` vers `.env`
2. Mettez à jour les variables :
   ```env
   DB_NAME=votre_bdd
   DB_USER=utilisateur_bdd
   DB_PASSWORD=votre_mdp
   ```

### 3. Base de Données
```sql
CREATE DATABASE calendrier_rdv;
# Importer le schéma SQL si nécessaire
```

### 4. Déploiement
- Mettez les fichiers sur le serveur via FTP/SFTP
- OU utilisez un pipeline CI/CD

### 5. Finalisation
1. Activez le plugin dans WordPress
2. Exécutez la configuration initiale
3. Testez les fonctionnalités clés

## Mise à Jour
1. Sauvegardez la base de données
2. Mettez à jour les fichiers
3. Exécutez les migrations si nécessaire

## Surveillance
- Vérifiez les logs d'erreurs PHP/WordPress
- Surveillez les performances
- Testez régulièrement les paiements

## Sécurité
- Mettez à jour régulièrement
- Utilisez HTTPS
- Restreignez les accès admin
- Sauvegardez régulièrement
