# Structure du dossier Docker

Tous les fichiers liés à la configuration et à l’exécution de l’environnement Docker sont désormais centralisés ici.

- `docker-compose.test.yml` : Configuration principale de l’environnement WordPress/MySQL pour le développement et les tests.
- `Dockerfile.test` : Image personnalisée pour les tests automatisés.
- `Dockerfile.wordpress` : Image personnalisée pour WordPress.

## Utilisation

Depuis la racine du projet :

```sh
docker-compose -f docker/docker-compose.test.yml up
```

## Raison du déplacement

Centralisation pour :
- Clarifier l’architecture
- Faciliter la maintenance et la CI/CD
- Éviter toute confusion lors des évolutions du projet

Voir README principal pour les instructions globales.
