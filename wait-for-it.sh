#!/bin/sh
# wait-for-it.sh
# Utilise wait-for-it pour attendre que la base de donnÃ©es soit prÃªte

set -e

host="$1"
shift
cmd="$@"

until mysqladmin ping -h "$host" --silent; do
  >&2 echo "La base de donnÃ©es n'est pas encore disponible - en attente..."
  sleep 1
done

>&2 echo "La base de donnÃ©es est prÃªte - exÃ©cution des commandes"
exec $cmd
