# Déploiement — apprentissage-JS

Mise en ligne du projet sur le VPS mutualisé, conformément au **chapitre 3** de
`fiches-migration-hebergement.md`.

**Sous-domaine cible** : `js.tondomaine.fr`
**Stack déployée** : deux conteneurs — `backend` (PHP 8.2-FPM) et `web` (nginx : SPA React + passerelle `/api`).

---

## Architecture de la mise en ligne

```
                    ┌──────────────────────────────────────────────┐
   Internet ──TLS──▶│ Traefik  (réseau `proxy`, partagé, hors repo)│
                    └───────────────────┬──────────────────────────┘
                                        │ HTTP, Host: js.tondomaine.fr
                    ┌───────────────────▼──────────────────────────┐
                    │ web  — nginx                                 │
                    │   /       → SPA React (fichiers statiques)   │
                    │   /api    → FastCGI vers backend:9000        │
                    └───────────────────┬──────────────────────────┘
                                        │ réseau `app` (privé au projet)
                    ┌───────────────────▼──────────────────────────┐
                    │ backend — php-fpm 8.2                        │
                    └───────────────────┬──────────────────────────┘
                                        │ réseau `shared-db`
                    ┌───────────────────▼──────────────────────────┐
                    │ MySQL mutualisée (hors repo)                 │
                    │   base `apprentissage_js` + user dédié       │
                    │   (voisine de `apprentissage_poo`)           │
                    └──────────────────────────────────────────────┘
```

**Le choix structurant : une seule origine.** nginx sert à la fois le SPA et l'API. Deux
conséquences directes :

- Le point « CORS » de la fiche est réglé par construction. Un appel du front vers `/api` est
  same-origin : le navigateur n'envoie pas d'en-tête `Origin` et ne fait aucune requête
  préliminaire. `CORS_ORIGIN` ne sert plus que de garde-fou pour d'éventuels appels externes.
- Le cookie httpOnly du refresh token reste en `SameSite=Lax`. S'il avait fallu séparer
  `api.tondomaine.fr` de `js.tondomaine.fr`, il aurait fallu passer en `SameSite=None`, donc
  accepter un cookie envoyé en contexte tiers.

Le code des apprenants, lui, ne s'exécute nulle part sur le serveur : il tourne dans le
navigateur (Web Worker / iframe sandboxée, `frontend/src/utils/jsSandbox.js`). C'est la
différence majeure avec le projet jumeau `apprentissage-POO-PHP`, et ce qui rend ce déploiement
nettement moins risqué que le sien.

---

## Prérequis sur le VPS

À faire **une fois pour toutes**, partagé avec les autres projets hébergés :

```bash
# Les deux réseaux Docker mutualisés
docker network create proxy
docker network create shared-db

# Vérifier que Traefik tourne et est bien branché sur `proxy`
docker network inspect proxy --format '{{range .Containers}}{{.Name}} {{end}}'
```

Traefik doit avoir un entrypoint `websecure` (443) et un resolver ACME nommé `letsencrypt` ; si
tes noms diffèrent, ajuste `TRAEFIK_ENTRYPOINT` / `TRAEFIK_CERTRESOLVER` dans `.env.docker`.

Enfin, un enregistrement DNS **A** `js.tondomaine.fr` → IP du VPS, propagé avant le premier
démarrage (sinon la demande de certificat Let's Encrypt échoue et Traefik la met en quarantaine
quelques minutes).

---

## 1. Créer la base et l'utilisateur MySQL

L'instance MySQL est mutualisée avec `apprentissage-POO-PHP`, mais **pas les permissions** :
chaque projet a son utilisateur, sans aucun droit sur la base de l'autre.

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
cd /srv/apprentissage-JS          # ou l'emplacement de ton clone
cp .env.docker.example .env.docker
chmod 600 .env.docker
```

Puis remplis `.env.docker`. Les valeurs à ne pas bâcler :

| Variable | Remarque |
|---|---|
| `APP_DOMAIN` | `js.tondomaine.fr` — sert à la règle de routage Traefik |
| `DB_HOST` | nom du conteneur MySQL **sur le réseau `shared-db`** |
| `DB_PASS` | celui défini à l'étape 1 |
| `JWT_SECRET` | **à générer**, jamais celui du dev ni celui du projet PHP jumeau |
| `API_URL` / `FRONTEND_URL` | `https://js.tondomaine.fr` — utilisés dans les liens des e-mails |
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
curl https://js.tondomaine.fr/api/health
#   {"status":"ok","timestamp":"...","version":"1.0.0"}

# Le certificat est bien émis
curl -sI https://js.tondomaine.fr | head -1

# CORS : une origine non listée ne doit recevoir AUCUN en-tête Access-Control-*
curl -sI -H "Origin: http://localhost:5200" https://js.tondomaine.fr/api/health \
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
| MySQL partagé, base + utilisateur dédiés | ✅ `docker/mysql/init-apprentissage-js.sql`, réseau `shared-db` |
| `.env` externalisé, jamais commité | ✅ `.env.docker` (ignoré par git) + `.env.docker.example` |
| Chemin PHP codé en dur (WAMP / PHP 7.4) | ✅ disparaît : PHP 8.2 est le PHP par défaut de l'image, et `scripts/` est exclu du build via `.dockerignore` |
| CORS restreint à `https://js.tondomaine.fr` | ✅ `Cors.php` réécrit — `CORS_ORIGIN` fait autorité, la tolérance `localhost` ne survit pas à `APP_ENV=production`. Et de toute façon sans objet : front et API sont same-origin |
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
| `MySQL injoignable après 60s` | `DB_HOST` incorrect, ou MySQL absent du réseau `shared-db` |
| `404 page not found` de Traefik | `APP_DOMAIN` ≠ DNS, ou service absent du réseau `proxy` |
| Erreur de connexion à la base alors que MySQL tourne | `clear_env`/`variables_order` : `docker compose exec backend php -r 'var_dump($_ENV["DB_HOST"]);'` |
| `Access denied for user` à la migration | droits manquants — rejouer l'étape 1 |
| Certificat non émis | DNS non propagé au premier démarrage ; attendre puis `docker compose restart web` |
| Éditeur de code vide, page blanche dans `/admin` | chunk Monaco non chargé — regarder l'onglet Réseau ; si une CSP a été activée, vérifier les violations en console |
