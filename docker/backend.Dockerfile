# Backend PHP 8.2 en PHP-FPM.
#
# Remplace le `php -S localhost:8010` de dev et, surtout, le PHP de WAMP :
# le PATH système de la machine de dev pointe vers PHP 7.4 et
# scripts/start-dev.ps1 contourne le problème en codant en dur le chemin de
# C:\wamp64\bin\php\php8.2.26\php.exe. Ici, PHP 8.2 EST le PHP par défaut de
# l'image — ce contournement n'a plus lieu d'être (fiche de migration, ch. 3).

FROM php:8.2-fpm-alpine

# pdo_mysql : accès à MySQL (le seul driver dont l'appli a besoin).
# opcache    : compilation en cache, indispensable en prod.
RUN docker-php-ext-install pdo_mysql opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Les dépendances sont installées avant le code applicatif pour que le cache de
# couche Docker ne soit invalidé que quand composer.lock change réellement.
COPY backend/composer.json backend/composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist

COPY backend/ ./
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

COPY docker/php/production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/php/zz-pool.conf /usr/local/etc/php-fpm.d/zz-pool.conf
COPY docker/backend-entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

# php-fpm écoute sur le réseau interne du compose ; il n'est jamais exposé
# directement au VPS ni à Traefik — seul le conteneur `web` (nginx) lui parle.
EXPOSE 9000

# Vérifie simplement que le socket FastCGI accepte les connexions ; la santé
# applicative complète (dont la base) est couverte par /api/health via nginx.
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD php -r 'exit(@fsockopen("127.0.0.1", 9000, $e, $s, 3) ? 0 : 1);'

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
