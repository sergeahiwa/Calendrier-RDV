# Tests pour Calendrier RDV

Ce répertoire contient les tests unitaires et d'intégration pour le plugin Calendrier RDV.

## Structure des répertoires

- `unit/` - Tests unitaires pour les fonctions et classes individuelles
- `integration/` - Tests d'intégration pour les points de terminaison API et les fonctionnalités complètes
- `_data/` - Données de test et fixtures

## Configuration requise

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Composer
- Extensions PHP requises : mbstring, xml, json, mysql, intl, dom, libxml, pdo, pdo_mysql, simplexml
- Git (pour cloner les tests WordPress)
- WP-CLI (recommandé pour la configuration)

## Installation

1. Installer les dépendances avec Composer :
   ```bash
   composer install
   ```

2. Configurer l'environnement de test WordPress :
   ```bash
   bash tests/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest true
   ```
   
   Paramètres :
   - `wordpress_test` : Nom de la base de données de test
   - `root` : Nom d'utilisateur MySQL
   - `root` : Mot de passe MySQL
   - `127.0.0.1` : Hôte MySQL
   - `latest` : Version de WordPress à utiliser pour les tests
   - `true` : Ne pas recréer la base de données si elle existe déjà

## Configuration de l'environnement de développement

1. Assurez-vous que MySQL est en cours d'exécution
2. Créez un fichier `phpunit.xml` à la racine du projet avec la configuration suivante :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         backupGlobals="false"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         >
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/integration</directory>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">./src</directory>
        </whitelist>
    </filter>
</phpunit>
```

## Exécution des tests

### Tous les tests
```bash
./vendor/bin/phpunit
```

### Tests unitaires uniquement
```bash
./vendor/bin/phpunit --testsuite Unit
```

### Tests d'intégration uniquement
```bash
./vendor/bin/phpunit --testsuite Integration
```

### Exécuter un fichier de test spécifique
```bash
./vendor/bin/phpunit tests/unit/ExampleTest.php
```

### Exécuter une méthode de test spécifique
```bash
./vendor/bin/phpunit --filter test_true_is_true tests/unit/ExampleTest.php
```

### Générer un rapport de couverture de code
```bash
./vendor/bin/phpunit --coverage-html coverage
```
Le rapport sera disponible dans le répertoire `coverage/`.

## Débogage des tests

1. Activez le mode débogage dans `tests/test-config.php` :
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('SAVEQUERIES', true);
   ```

2. Consultez les journaux d'erreur dans `tests/error.log`

## Écrire de nouveaux tests

### Structure d'un test unitaire

```php
<?php

class ExampleTest extends WP_UnitTestCase {
    
    public function setUp() {
        parent::setUp();
        // Code à exécuter avant chaque test
    }
    
    public function tearDown() {
        // Code à exécuter après chaque test
        parent::tearDown();
    }
    
    public function test_example() {
        // Votre test ici
        $this->assertTrue(true);
    }
}
```

### Bonnes pratiques

1. Un test ne doit tester qu'une seule chose
2. Utilisez des noms de méthodes descriptifs
3. Utilisez les assertions appropriées
4. Nettoyez après chaque test
5. Utilisez des fixtures pour les données de test
6. Testez les cas limites et les erreurs

## Intégration continue

Le projet est configuré avec GitHub Actions pour exécuter automatiquement les tests à chaque push ou pull request. Le fichier de configuration se trouve dans `.github/workflows/php-tests.yml`.
Les tests unitaires doivent être placés dans le répertoire `Unit/` et doivent étendre `WP_UnitTestCase`.

### Tests d'intégration
Les tests d'intégration doivent être placés dans le répertoire `Integration/` et doivent étendre notre classe `TestCase` personnalisée.

## Bonnes pratiques

- Un test par cas d'utilisation
- Nommer les méthodes de test avec le préfixe `test_`
- Utiliser des données de test réalistes
- Nettoyer les données après chaque test
- Documenter les tests complexes

## Dépannage

### Erreurs de base de données
Assurez-vous que l'utilisateur MySQL a les droits nécessaires pour créer et modifier des bases de données.

### Problèmes de chargement de WordPress
Vérifiez que le script d'installation s'est bien exécuté et que les variables d'environnement sont correctement définies.

### Problèmes de dépendances
Exécutez `composer update` pour mettre à jour les dépendances.
