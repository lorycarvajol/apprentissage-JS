-- Initialisation de ce projet sur l'instance MySQL mutualisée du VPS.
--
-- À jouer UNE SEULE FOIS, en tant qu'administrateur MySQL, avant le premier
-- `docker compose up` :
--
--   docker exec -i <conteneur-mysql> mysql -uroot -p \
--       < docker/mysql/init-apprentissage-js.sql
--
-- Il ne crée que la base et l'utilisateur : le schéma (tables + badges) est
-- appliqué ensuite automatiquement par database/migrate.php, lancé au
-- démarrage du conteneur backend.
--
-- Le principe de la fiche de migration est « instance partagée, permissions
-- cloisonnées » : apprentissage-JS et apprentissage-POO-PHP cohabitent sur le
-- même serveur MySQL, mais chacun avec son utilisateur, sans aucun droit sur
-- la base de l'autre.

CREATE DATABASE IF NOT EXISTS apprentissage_js
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- REMPLACER le mot de passe ci-dessous avant exécution, et reporter la même
-- valeur dans DB_PASS du fichier .env.docker.
CREATE USER IF NOT EXISTS 'apprentissage_js'@'%'
    IDENTIFIED BY 'REMPLACER_PAR_UN_MOT_DE_PASSE_FORT';

-- Droits volontairement limités au strict nécessaire, sur cette base
-- uniquement. CREATE/ALTER/INDEX/REFERENCES sont indispensables parce que
-- migrate.php applique le schéma au démarrage. DROP n'est PAS accordé :
-- schema.sql n'en a pas besoin (tout est en CREATE ... IF NOT EXISTS), et son
-- absence empêche une injection SQL qui passerait les défenses applicatives de
-- supprimer quoi que ce soit.
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
    ON apprentissage_js.*
    TO 'apprentissage_js'@'%';

FLUSH PRIVILEGES;

-- Vérification rapide des droits accordés :
--   SHOW GRANTS FOR 'apprentissage_js'@'%';
