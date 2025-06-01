#!/bin/bash
set -e

# Afficher les variables d'environnement pour le dÃ©bogage
echo "=== Configuration de la base de donnÃ©es ==="
echo "DB_HOST: ${WORDPRESS_DB_HOST}"
echo "DB_NAME: ${WORDPRESS_DB_NAME}"
echo "DB_USER: ${WORDPRESS_DB_USER}"

# CrÃ©er le rÃ©pertoire des tests WordPress s'il n'existe pas
mkdir -p /tmp/wordpress-tests-lib

# TÃ©lÃ©charger la librairie de tests WordPress si nÃ©cessaire
if [ ! -f /tmp/wordpress-tests-lib/includes/functions.php ]; then
    echo "TÃ©lÃ©chargement de la bibliothÃ¨que de tests WordPress..."
    curl -o /tmp/wordpress-tests-lib.tar.gz https://develop.svn.wordpress.org/trunk/tests/phpunit/data/wordpress-tests-lib.tar.gz
    tar --strip-components=1 -C /tmp/wordpress-tests-lib -xzf /tmp/wordpress-tests-lib.tar.gz
    rm -f /tmp/wordpress-tests-lib.tar.gz
fi

# CrÃ©er le fichier de configuration de test
WP_TESTS_CONFIG="/tmp/wordpress-tests-lib/wp-tests-config.php"

echo "CrÃ©ation du fichier de configuration de test: ${WP_TESTS_CONFIG}"

cat > "${WP_TESTS_CONFIG}" <<EOL
<?php
define('DB_NAME', '${WORDPRESS_DB_NAME}');
define('DB_USER', '${WORDPRESS_DB_USER}');
define('DB_PASSWORD', '${WORDPRESS_DB_PASSWORD}');
define('DB_HOST', '${WORDPRESS_DB_HOST}');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');

define('WP_PHP_BINARY', 'php');

define('WPLANG', '');

// Mode test
\$GLOBALS['wp_tests_options'] = array(
    'active_plugins' => array('calendrier-rdv/calendrier-rdv.php'),
);
EOL

# VÃ©rifier que PHPUnit est disponible
if [ ! -f "./vendor/bin/phpunit" ]; then
    echo "Installation des dÃ©pendances Composer..."
    composer install --prefer-dist --no-interaction --no-scripts
fi

# VÃ©rifier que la configuration PHPUnit existe
if [ ! -f "./tests/phpunit.xml" ]; then
    echo "CrÃ©ation du fichier de configuration PHPUnit par dÃ©faut..."
    cat > "./tests/phpunit.xml" << 'EOL'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    bootstrap="bootstrap.php"
    colors="true"
    beStrictOnOutputDuringTests="true"
    beStrictAboutTestsThatDoNotTestAnything="true"
    beStrictAboutChangesToGlobalState="true"
    beStrictAboutCoversAnnotation="false"
    verbose="true"
    stopOnError="false"
    stopOnFailure="false"
    stopOnIncomplete="false"
    stopOnSkipped="false"
    stopOnRisky="false"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    processIsolation="false"
    backupGlobals="false"
    backupStaticAttributes="false"
    cacheResult="true"
    cacheTokens="true"
>
    <testsuites>
        <testsuite name="Unit Tests">
            <directory suffix="Test.php">./tests/unit</directory>
        </testsuite>
        <testsuite name="Integration Tests">
            <directory suffix="Test.php">./tests/integration</directory>
        </testsuite>
        <testsuite name="Functional Tests">
            <directory suffix="Test.php">./tests/functional</directory>
        </testsuite>
    </testsuites>

    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./src</directory>
        </include>
        <exclude>
            <directory suffix=".php">./vendor</directory>
        </exclude>
    </coverage>

    <php>
        <const name="WP_TESTS_CONFIG_FILE_PATH" value="/tmp/wordpress-tests-lib/wp-tests-config.php" />
        <const name="WP_TESTS_DIR" value="/tmp/wordpress-tests-lib" />
        <const name="WP_ROOT_DIR" value="/tmp/wordpress" />
        <ini name="display_errors" value="1"/>
        <ini name="error_reporting" value="-1"/>
        <ini name="memory_limit" value="-1"/>
    </php>
</phpunit>
EOL
fi

echo "=== Configuration terminÃ©e ==="
echo "ExÃ©cution des tests PHPUnit..."

# ExÃ©cuter les tests
./vendor/bin/phpunit -c tests/phpunit.xml --testdox
