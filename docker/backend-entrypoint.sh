#!/bin/sh
# Entrypoint du conteneur backend.
#
# Deux responsabilités avant de céder la main à php-fpm :
#   1. attendre que MySQL (instance partagée du VPS, hors de ce compose)
#      accepte les connexions — sans ça le premier démarrage après un reboot
#      du VPS échoue selon l'ordre de remontée des conteneurs ;
#   2. jouer database/migrate.php, pour que le schéma soit appliqué
#      automatiquement au déploiement au lieu d'être lancé à la main
#      (fiche de migration, ch. 3).
set -e

# export, et pas une simple affectation : la boucle d'attente ci-dessous lit ces
# valeurs via getenv() depuis un sous-processus php, qui ne voit que
# l'environnement exporté.
export DB_HOST="${DB_HOST:-mysql}"
export DB_PORT="${DB_PORT:-3306}"
DB_WAIT_TIMEOUT="${DB_WAIT_TIMEOUT:-60}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"

echo "[entrypoint] Attente de MySQL sur ${DB_HOST}:${DB_PORT} (timeout ${DB_WAIT_TIMEOUT}s)..."

elapsed=0
until php -r 'exit(@fsockopen(getenv("DB_HOST"), (int) getenv("DB_PORT"), $e, $s, 2) ? 0 : 1);'; do
    elapsed=$((elapsed + 2))
    if [ "$elapsed" -ge "$DB_WAIT_TIMEOUT" ]; then
        echo "[entrypoint] MySQL injoignable après ${DB_WAIT_TIMEOUT}s — abandon." >&2
        exit 1
    fi
    sleep 2
done

echo "[entrypoint] MySQL est joignable."

if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "[entrypoint] Application du schéma (database/migrate.php)..."
    # migrate.php est idempotent : CREATE DATABASE/TABLE IF NOT EXISTS, et les
    # erreurs "already exists"/"Duplicate entry" sont ignorées. Le relancer à
    # chaque démarrage est donc sans effet de bord sur une base déjà à jour.
    php /var/www/html/database/migrate.php
else
    echo "[entrypoint] RUN_MIGRATIONS=false — migration ignorée."
fi

echo "[entrypoint] Démarrage de : $*"
exec "$@"
