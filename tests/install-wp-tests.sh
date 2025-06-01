#!/usr/bin/env bash
# Script d'installation des tests WordPress

if [ $# -lt 1 ]; then
    echo "Usage: $0 <db-name> [db-user] [db-pass] [db-host] [wp-version] [skip-database-creation]"
    exit 1
fi

DB_NAME=$1
DB_USER=${2-root}
DB_PASS=${3-''}
DB_HOST=${4-127.0.0.1}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

# Dossiers de travail
WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress/}

# Arrêter en cas d'erreur
set -e

# Fonction pour afficher les messages d'information
function info() {
    echo -e "\033[0;36m$1\033[0m"
}

# Créer la base de données si nécessaire
if [ "$SKIP_DB_CREATE" != "true" ]; then
    info "Création de la base de données $DB_NAME..."
    mysql -u "$DB_USER" $(if [ -n "$DB_PASS" ]; then echo "-p$DB_PASS"; fi) -h "$DB_HOST" -e "DROP DATABASE IF EXISTS $DB_NAME"
    mysql -u "$DB_USER" $(if [ -n "$DB_PASS" ]; then echo "-p$DB_PASS"; fi) -h "$DB_HOST" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME"
fi

# Télécharger WordPress
if [ ! -d $WP_CORE_DIR ]; then
    info "Téléchargement de WordPress..."
    mkdir -p $WP_CORE_DIR
    wp core download --version=$WP_VERSION --path=$WP_CORE_DIR --quiet
    
    # Configurer WordPress
    info "Configuration de WordPress..."
    wp config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASS" --dbhost="$DB_HOST" --path=$WP_CORE_DIR --skip-check --force
    
    # Installer WordPress
    info "Installation de WordPress..."
    wp core install --url="http://example.org" --title="Test" --admin_user="admin" --admin_password="admin" --admin_email="admin@example.org" --path=$WP_CORE_DIR
    
    # Installer les tests
    if [ ! -d $WP_TESTS_DIR ]; then
        info "Installation des tests WordPress..."
        mkdir -p $WP_TESTS_DIR
        svn co --quiet https://develop.svn.wordpress.org/tags/$(wp core version --path=$WP_CORE_DIR)/tests/phpunit/includes/ $WP_TESTS_DIR/includes
        svn co --quiet https://develop.svn.wordpress.org/tags/$(wp core version --path=$WP_CORE_DIR)/tests/phpunit/data/ $WP_TESTS_DIR/data
    fi
    
    # Copier le fichier de configuration des tests
    if [ ! -f wp-tests-config.php ]; then
        cp $WP_TESTS_DIR/includes/bootstrap.php wp-tests-config.php
    fi
    
    info "Configuration des tests terminée !"
else
    info "WordPress est déjà installé dans $WP_CORE_DIR"
fi
