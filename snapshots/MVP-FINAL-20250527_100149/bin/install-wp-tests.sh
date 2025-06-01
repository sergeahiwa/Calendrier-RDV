#!/bin/bash

# Script d'installation de l'environnement de test WordPress
# Basé sur le script officiel de WordPress

if [ $# -lt 3 ]; then
    echo "Utilisation: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
    exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

WP_TESTS_DIR=${WP_TESTS_DIR-"/tmp/wordpress-tests-lib"}
WP_CORE_DIR=${WP_CORE_DIR-"/tmp/wordpress/"}

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

if [[ $WP_VERSION =~ [0-9]+\.[0-9]+(\.[0-9]+)? ]]; then
    WP_TESTS_TAG="tags/$WP_VERSION"
elif [ "$WP_VERSION" == "nightly" ] || [ "$WP_VERSION" == "trunk" ]; then
    WP_TESTS_TAG="trunk"
else
    # http sert une seule offre, alors que https en sert plusieurs. Nous n'en voulons qu'une
    download http://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
    grep '[0-9]+\.[0-9]+(\.[0-9]+)?' /tmp/wp-latest.json
    local LATEST_VERSION=$(grep -o '"version":"[^"]*' /tmp/wp-latest.json | sed 's/"version":"\(.*\)/\1/' | head -1)
    if [ -z "$LATEST_VERSION" ]; then
        echo "Impossible de trouver la dernière version de WordPress. Réessayez plus tard."
        exit 1
    fi
    WP_TESTS_TAG="tags/$LATEST_VERSION"
fi

set -ex

install_wp() {
    if [ -d $WP_CORE_DIR ]; then
        return;
    fi

    mkdir -p $WP_CORE_DIR

    if [ $WP_VERSION == 'latest' ] || [ $WP_VERSION == 'trunk' ]; then
        local ARCHIVE_NAME='trunk'
    else
        local ARCHIVE_NAME="wordpress-$WP_VERSION"
    fi

    download https://wordpress.org/${ARCHIVE_NAME}.tar.gz /tmp/wordpress.tar.gz
    tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR

    download https://raw.github.com/markoheijnen/wp-mysqli/master/db.php $WP_CORE_DIR/wp-content/db.php
}

install_test_suite() {
    # configurer la suite de tests si elle n'existe pas encore
    if [ ! -d $WP_TESTS_DIR ]; then
        # configurer la suite de tests
        mkdir -p $WP_TESTS_DIR
        svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
        svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $WP_TESTS_DIR/data
    fi

    if [ ! -f wp-tests-config.php ]; then
        download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config.php
        # supprimer tous les slashes à la fin
        WP_CORE_DIR=$(echo $WP_CORE_DIR | sed "s:/\+$::")
        sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
        sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
        sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
        sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
        sed $ioption "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR"/wp-tests-config.php
    fi
}

install_db() {
    if [ "$SKIP_DB_CREATE" = "true" ]; then
        return 0
    fi

    # analyser DB_HOST pour les références de port ou de socket
    local PARTS=(${DB_HOST/\// })
    local DB_HOSTNAME=${PARTS[0]};
    local DB_SOCK_OR_PORT=${PARTS[1]};
    local EXTRA=""

    if ! [ -z $DB_HOSTNAME ] ; then
        if [ "$(echo $DB_SOCK_OR_PORT | grep -o '[0-9]\+$')" != "$DB_SOCK_OR_PORT" ] ; then
            # DB_HOST manque un port ou un socket
            EXTRA=" --host=$DB_HOSTNAME --socket=$DB_SOCK_OR_PORT"
        else
            EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT"
        fi
    fi

    # créer la base de données
    RESULT=$(mysql -u $DB_USER --password="$DB_PASS" --skip-column-names -e "SHOW DATABASES LIKE '$DB_NAME'" $EXTRA)
    if [ "$RESULT" != "$DB_NAME" ]; then
        mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
    fi
}

install_wp
install_test_suite
install_db
