# Frontend : build Vite puis service des fichiers statiques par nginx.
#
# Ce même nginx est aussi la porte d'entrée de l'API : il sert le SPA sur `/`
# et transmet `/api` à php-fpm (conteneur `backend`) en FastCGI. Conséquence
# volontaire : le front et l'API partagent une seule origine
# (https://js.tondomaine.fr), ce qui règle d'un coup le point « CORS » de la
# fiche de migration — un appel same-origin n'envoie pas d'en-tête Origin, donc
# aucune requête préliminaire (preflight) — et fait fonctionner le cookie
# httpOnly du refresh token sans avoir à passer en SameSite=None.

# ---------------------------------------------------------------------------
# Étape 1 : build
# ---------------------------------------------------------------------------
FROM node:22-alpine AS build

WORKDIR /app

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend/ ./

# Vite inline les variables VITE_* au moment du build : c'est un argument de
# build, pas une variable de runtime. La valeur par défaut « /api » est
# relative, donc valable quel que soit le domaine final.
ARG VITE_API_URL=/api
ENV VITE_API_URL=$VITE_API_URL

RUN npm run build

# ---------------------------------------------------------------------------
# Étape 2 : service
# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine

COPY docker/nginx/security-headers.conf /etc/nginx/snippets/security-headers.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://127.0.0.1/healthz || exit 1
