# Déploiement — apprentissage-JS

Mise en ligne du projet sur le VPS mutualisé, conformément au **chapitre 3** de
`fiches-migration-hebergement.md`.

**Sous-domaine cible** : `js.lorycarvajol.dev`
**Stack déployée** : deux conteneurs — `backend` (PHP 8.2-FPM) et `web` (nginx : SPA React + passerelle `/api`).

---

## Architecture de la mise en ligne

```
                    ┌──────────────────────────────────────────────┐
   Internet ──TLS──▶│ Traefik  (réseau `proxy`, partagé, hors repo)│
                    └───────────────────┬──────────────────────────┘
                                        │ HTTP, Host: js.lorycarvajol.dev
                    ┌───────────────────▼──────────────────────────┐
                    │ web  — nginx                                 │
                    │   /       → SPA React (fichiers statiques)   │
                    │   /api    → FastCGI vers backend:9000        │
                    └───────────────────┬──────────────────────────┘
                                        │ réseau `app` (privé au projet)
                    ┌───────────────────▼──────────────────────────┐
                    │ backend — php-fpm 8.2                        │
                    └───────────────────┬──────────────────────────┘
                                        │ réseau `mysql-shared`
                    ┌───────────────────▼──────────────────────────┐
                    │ MySQL mutualisée (hors repo)                 │
                    │   conteneur `mysql-shared-mysql-1`           │
                    │   base `apprentissage_js` + user dédié       │
                    │   (voisine de `tapistyle`)                   │
                    └──────────────────────────────────────────────┘
```

**Le choix structurant : une seule origine.** nginx sert à la fois le SPA et l'API. Deux
conséquences directes :

- Le point « CORS » de la fiche est réglé par construction. Un appel du front vers `/api` est
  same-origin : le navigateur n'envoie pas d'en-tête `Origin` et ne fait aucune requête
  préliminaire. `CORS_ORIGIN` ne sert plus que de garde-fou pour d'éventuels appels externes.
- Le cookie httpOnly du refresh token reste en `SameSite=Lax`. S'il avait fallu séparer
  `api.lorycarvajol.dev` de `js.lorycarvajol.dev`, il aurait fallu passer en `SameSite=None`, donc
  accepter un cookie envoyé en contexte tiers.

Le code des apprenants, lui, ne s'exécute nulle part sur le serveur : il tourne dans le
navigateur (Web Worker / iframe sandboxée, `frontend/src/utils/jsSandbox.js`). C'est la
différence majeure avec le projet jumeau `apprentissage-POO-PHP`, et ce qui rend ce déploiement
nettement moins risqué que le sien.

---

## Prérequis sur le VPS

**Tout est déjà en place sur le VPS** — vérifié le 17/08/2026. Rien à créer ; ce qui suit
documente l'existant, et sert à revalider après une réinstallation.

| Ressource | Valeur réelle | Vérification |
|---|---|---|
| Réseau Traefik | `proxy` | `docker network inspect proxy --format '{{range .Containers}}{{.Name}} {{end}}'` |
| Réseau MySQL | `mysql-shared` (**pas** `shared-db`) | `docker network ls` |
| Conteneur MySQL | `mysql-shared-mysql-1`, MySQL 8.0.46 | `docker exec mysql-shared-mysql-1 mysql --version` |
| Entrypoint TLS | `websecure` (443), avec redirection depuis `web` (80) | `docker inspect traefik-traefik-1` |
| Resolver ACME | `myresolver` (**pas** `letsencrypt`), challenge HTTP-01 | idem |
| DNS | `js.lorycarvajol.dev` → `51.75.194.109` | `dig +short js.lorycarvajol.dev` |

Deux de ces valeurs diffèrent des noms « canoniques » de la fiche de migration et sont la cause
d'échec la plus probable d'un premier déploiement : le réseau MySQL s'appelle `mysql-shared`, et
le resolver ACME `myresolver`. Les deux sont renseignés dans `.env.docker.example`, et les valeurs
par défaut de `docker-compose.yml` ont été alignées dessus.

L'enregistrement DNS **A** doit être propagé **avant** le premier démarrage, sinon la demande de
certificat Let's Encrypt échoue et Traefik la met en quarantaine quelques minutes.

---

## 1. Créer la base et l'utilisateur MySQL

L'instance MySQL (`mysql-shared-mysql-1`) est mutualisée avec les autres projets du VPS —
`tapistyle` aujourd'hui, `apprentissage-POO-PHP` le jour où il sera déployé — mais **pas les
permissions** : chaque projet a son utilisateur, sans aucun droit sur la base des autres.

Ouvre `docker/mysql/init-apprentissage-js.sql`, remplace le mot de passe d'exemple par un mot de
passe fort, puis :

```bash
docker exec -i <conteneur-mysql> mysql -uroot -p < docker/mysql/init-apprentissage-js.sql

# Vérification
docker exec -it <conteneur-mysql> mysql -uroot -p \
    -e "SHOW GRANTS FOR 'apprentissage_js'@'%';"
```

Le script ne crée que la base et l'utilisateur. Les tables et les badges sont posés
automatiquement à l'étape 3 par `database/migrate.php`.

> `DROP` n'est volontairement pas accordé : `schema.sql` n'en a pas besoin (tout est en
> `CREATE ... IF NOT EXISTS`), et son absence limite les dégâts d'une injection SQL qui
> passerait les défenses applicatives.

---

## 2. Renseigner les secrets

```bash
cd ~/apps/apprentissage-JS       # les projets du VPS vivent dans ~/apps
cp .env.docker.example .env.docker
chmod 600 .env.docker
```

Puis remplis `.env.docker`. Les valeurs à ne pas bâcler :

| Variable | Remarque |
|---|---|
| `APP_DOMAIN` | `js.lorycarvajol.dev` — sert à la règle de routage Traefik |
| `TRAEFIK_CERTRESOLVER` | `myresolver` — le nom réel du resolver ACME de ce VPS |
| `DB_HOST` | `mysql-shared-mysql-1` — le conteneur MySQL sur le réseau `mysql-shared` |
| `DB_PASS` | celui défini à l'étape 1 |
| `JWT_SECRET` | **à générer**, jamais celui du dev ni celui du projet PHP jumeau |
| `API_URL` / `FRONTEND_URL` | `https://js.lorycarvajol.dev` — utilisés dans les liens des e-mails |
| `MAIL_*` | un vrai relais SMTP : Mailtrap ne délivre rien aux vrais destinataires |

```bash
openssl rand -hex 32        # valeur pour JWT_SECRET
```

> `JWT_SECRET` doit être unique à cette instance. Réutiliser celui du projet PHP jumeau
> permettrait à un token émis par l'un d'être accepté par l'autre. Le code refuse d'ailleurs
> de démarrer sur la valeur d'exemple (`JwtConfig`, cf. `testPlaceholderSecretThrowsRuntimeException`).

`.env.docker` est ignoré par git. Seul `.env.docker.example`, sans valeurs, est versionné.

---

## 3. Déployer

```bash
docker compose --env-file .env.docker up -d --build
```

Le `--env-file` n'est pas optionnel : il alimente à la fois l'interpolation des `${VARIABLES}` du
`docker-compose.yml` et l'environnement injecté dans le conteneur backend.

Au démarrage, `docker/backend-entrypoint.sh` attend que MySQL réponde, puis joue
`database/migrate.php` — le schéma est donc appliqué **automatiquement**, sans commande manuelle
(point explicitement demandé par la fiche). Le script est idempotent : le relancer à chaque
redémarrage est sans effet sur une base déjà à jour.

---

## 4. Vérifier

```bash
# Le backend a bien vu MySQL et appliqué le schéma
docker compose --env-file .env.docker logs backend | head -30
#   [entrypoint] MySQL est joignable.
#   [entrypoint] Application du schéma (database/migrate.php)...
#   ✓ Migration terminée avec succès !

# L'API répond à travers Traefik
curl https://js.lorycarvajol.dev/api/health
#   {"status":"ok","timestamp":"...","version":"1.0.0"}

# Le certificat est bien émis
curl -sI https://js.lorycarvajol.dev | head -1

# CORS : une origine non listée ne doit recevoir AUCUN en-tête Access-Control-*
curl -sI -H "Origin: http://localhost:5200" https://js.lorycarvajol.dev/api/health \
    | grep -i access-control
#   (aucune sortie attendue)

# État des conteneurs
docker compose --env-file .env.docker ps
```

Puis, dans un navigateur :

1. Créer le premier compte, le passer en `admin` :
   `UPDATE users SET role = 'admin' WHERE email = 'ton@email.fr';`
2. Vérifier que l'e-mail de vérification arrive réellement (config `MAIL_*`).
3. Se déconnecter / reconnecter, laisser l'access token expirer (15 min) et confirmer que le
   rafraîchissement automatique fonctionne — c'est le test du cookie httpOnly derrière Traefik.
4. Créer un module / chapitre / exercice via `/admin`, puis résoudre l'exercice : l'éditeur
   Monaco doit s'afficher et la soumission être notée. Cela valide toute la chaîne
   Worker → `/api/exercices/{id}/submit` → comparaison à `expected_output`.

---

## Mise à jour et retour arrière

```bash
git pull
docker compose --env-file .env.docker up -d --build
```

**Un simple `git pull` ne suffit jamais.** Le code vit dans l'image, et `opcache` tourne avec
`validate_timestamps=0` (`docker/php/production.ini`) : PHP ne relit pas les fichiers. Toute mise
à jour passe obligatoirement par une reconstruction d'image et un redémarrage du conteneur.

Retour arrière : les images sont taguées `latest`, donc écrasées à chaque build. Pour pouvoir
revenir en arrière, tague explicitement avant de déployer :

```bash
docker tag apprentissage-js-backend:latest apprentissage-js-backend:$(date +%Y%m%d)
docker tag apprentissage-js-web:latest     apprentissage-js-web:$(date +%Y%m%d)
```

---

## La CSP

Une `Content-Security-Policy` est **active** (`docker/nginx/security-headers.conf`). Sa directive
essentielle est `connect-src 'self'` : le code JS soumis par un apprenant ne peut émettre aucune
requête vers un domaine tiers. C'est une barrière indépendante de la neutralisation des APIs
réseau faite dans le bac à sable — si l'une était contournée, l'autre tiendrait.

Elle n'accorde **aucune exception à un domaine externe**, ce qui n'a été possible qu'après avoir
auto-hébergé les deux ressources tierces que l'application chargeait à l'exécution : Monaco et les
polices Google. Toute nouvelle ressource tierce doit être empaquetée plutôt qu'ajoutée à la liste
blanche : un CDN de plus dans `connect-src`/`script-src`, et le verrou saute.

### Ce qui a été vérifié

Sur un build identique à celui de production, servi avec cet en-tête exact et un `report-uri`
collectant les violations, dans Chrome :

| Test | Résultat |
|---|---|
| Chargement de l'application | 0 violation |
| Worker créé depuis un Blob + `new Function` (`runJs`) | exécution OK |
| iframe `srcdoc` sandboxée, `<script>` inline, accès DOM (`runJsWithDom`) | OK |
| Chunk Monaco chargé à la demande + démarrage de ses workers | OK |
| `fetch(..., {mode:'no-cors'})` vers un tiers, depuis la page | bloqué |
| idem depuis le Worker du bac à sable | bloqué |
| WebSocket et image vers un tiers | bloqués |

Le `mode:'no-cors'` est important : un `fetch` classique vers un domaine tiers échoue de toute
façon pour cause de CORS, ce qui masquerait une CSP inopérante. En `no-cors`, la requête
aboutirait normalement — son échec prouve donc bien que c'est la CSP qui bloque.

### Revalider la CSP

À refaire si une ressource externe est ajoutée, ou après une montée de version de Monaco :

1. `docker compose --env-file .env.docker up -d --build web`.
2. Console du navigateur ouverte, exercer les trois chemins qui utilisent `eval`/`blob`/`srcdoc` :
   - un exercice **sans** `html_fixture` (bac à sable Web Worker) ;
   - un exercice **avec** `html_fixture` (iframe `srcdoc`) ;
   - l'éditeur Monaco dans `/admin` et dans un exercice.
3. Zéro message `Refused to ...` ⇒ c'est bon. Sinon, corriger la cause (auto-héberger la
   ressource) de préférence à l'ajout d'une exception dans la CSP.

---

## Correspondance avec la checklist du chapitre 3

| Point de la fiche | État |
|---|---|
| Créer un `Dockerfile` backend PHP-FPM | ✅ `docker/backend.Dockerfile` |
| MySQL partagé, base + utilisateur dédiés | ✅ `docker/mysql/init-apprentissage-js.sql`, réseau `mysql-shared` |
| `.env` externalisé, jamais commité | ✅ `.env.docker` (ignoré par git) + `.env.docker.example` |
| Chemin PHP codé en dur (WAMP / PHP 7.4) | ✅ disparaît : PHP 8.2 est le PHP par défaut de l'image, et `scripts/` est exclu du build via `.dockerignore` |
| CORS restreint à `https://js.lorycarvajol.dev` | ✅ `Cors.php` réécrit — `CORS_ORIGIN` fait autorité, la tolérance `localhost` ne survit pas à `APP_ENV=production`. Et de toute façon sans objet : front et API sont same-origin |
| Sandbox Web Worker : timeout + APIs sensibles | ✅ timeout 5 s déjà en place ; `jsSandbox.js` neutralise désormais aussi `WebSocket`, `EventSource`, `BroadcastChannel`, `indexedDB`, `caches`, `sendBeacon` et **`Worker`** (un worker imbriqué repartait avec un `fetch` intact) |
| `migrate.php` joué au démarrage du conteneur | ✅ `docker/backend-entrypoint.sh` |

### Traité en plus, non listé dans la fiche

- **Content-Security-Policy active et vérifiée** — voir la section « La CSP » ci-dessus.
- **Monaco n'est plus chargé depuis un CDN tiers.** `@monaco-editor/react` le téléchargeait
  depuis `cdn.jsdelivr.net` à l'exécution : script tiers avec les pleins droits sur la page
  (dont l'accès au JWT gardé en mémoire), éditeur en panne si le CDN tombe, et IP de chaque
  visiteur transmise à un tiers — à savoir si des mentions RGPD sont publiées. Monaco est
  maintenant empaqueté (`frontend/src/utils/monacoSetup.js`) et chargé à la demande
  (`components/common/CodeEditor.jsx`), pour que la page de connexion n'en paie pas les ~4 Mo.
- **Les polices non plus.** `index.html` chargeait quatre familles depuis `fonts.googleapis.com`
  (Cinzel, Cormorant Garamond, Space Grotesk, IBM Plex Mono) — découvert en testant la CSP, qui
  les bloquait. Mêmes objections que pour Monaco, et même correctif : elles sont empaquetées via
  `@fontsource` (`frontend/src/styles/fonts.css`, poids identiques à ceux que demandait l'ancienne
  URL Google). C'est ce qui permet à la CSP de rester strictement en `'self'`.
- **IP réelle du client.** `set_real_ip_from` dans la conf nginx. Sans cela, `REMOTE_ADDR` aurait
  valu l'IP de Traefik pour tout le monde : la limitation de débit et l'historique de connexion
  auraient traité l'ensemble des visiteurs comme un seul client, et quelques échecs de connexion
  auraient suffi à verrouiller la plateforme entière.
- **`clear_env = no`** dans le pool php-fpm et **`variables_order = "EGPCS"`** dans la conf PHP.
  Sans l'un ou l'autre, `$_ENV` reste vide en conteneur et l'application démarre silencieusement
  sur ses valeurs de développement.

---

## Dépannage

| Symptôme | Cause probable |
|---|---|
| `502 Bad Gateway` sur `/api` | conteneur `backend` arrêté — `docker compose logs backend` |
| `MySQL injoignable après 60s` | `DB_HOST` incorrect, ou `DB_NETWORK` ≠ `mysql-shared` |
| `404 page not found` de Traefik | `APP_DOMAIN` ≠ DNS, ou service absent du réseau `proxy` |
| Erreur de connexion à la base alors que MySQL tourne | `clear_env`/`variables_order` : `docker compose exec backend php -r 'var_dump($_ENV["DB_HOST"]);'` |
| `Access denied for user` à la migration | droits manquants — rejouer l'étape 1 |
| Certificat non émis | `TRAEFIK_CERTRESOLVER` ≠ `myresolver`, ou DNS non propagé au premier démarrage ; corriger puis `docker compose up -d --force-recreate web` |
| Éditeur de code vide, page blanche dans `/admin` | chunk Monaco non chargé — regarder l'onglet Réseau ; si une CSP a été activée, vérifier les violations en console |
