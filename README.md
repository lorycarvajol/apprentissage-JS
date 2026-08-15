# Plateforme d'Apprentissage JavaScript

Plateforme d'apprentissage interactive pour enseigner la Programmation Orientée Objet en JavaScript.

Sibling project de `apprentissage-POO-PHP` : même architecture, même moteur (backend PHP hand-rolled MVC +
React/Vite), mais contenu de cours en JavaScript et exécution du code de l'apprenant côté client (Web Worker)
plutôt que côté serveur. Voir `CLAUDE.md` pour le détail des différences.

Aucun contenu de cours n'est pré-rempli : la base est vide (schéma + badges seulement), à remplir via `/admin`.

## 🛠 Stack Technique

### Backend
- PHP 8.2+
- MySQL 8.0
- Composer
- JWT Authentication (firebase/php-jwt)
- PHPUnit (installé, pas encore de suite de tests)

### Frontend
- React 19
- Vite
- React Router 7
- Axios
- React Query
- Monaco Editor
- Chart.js

## 📁 Structure du Projet

```
apprentissage-JS/
├── backend/
│   ├── src/
│   │   ├── Config/       # Database, CORS, JWT
│   │   ├── Controllers/  # Contrôleurs API
│   │   ├── Models/       # Modèles de données
│   │   ├── Services/     # Logique métier
│   │   ├── Middleware/   # Middleware
│   │   └── Helpers/      # Router, Response
│   ├── database/         # schema.sql, migrate.php
│   ├── public/           # Point d'entrée (index.php)
│   └── composer.json
│
└── frontend/
    ├── src/
    │   ├── components/   # admin/, auth/, common/, content/, layout/
    │   ├── pages/
    │   ├── services/     # api.js, contentService.js, adminService.js, gamificationService.js
    │   ├── utils/         # jsSandbox.js (exécution client), jsErrorTranslator.js
    │   └── styles/
    └── package.json
```

## 🚀 Installation

### Prérequis
- PHP 8.2+ (le PATH système pointe vers 7.4 sur cette machine — voir `scripts/start-dev.ps1`)
- Node.js 18+
- MySQL 8.0 (instance partagée avec `apprentissage-POO-PHP`)

### Démarrage rapide

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start-dev.ps1
```

Démarre MySQL (si besoin), le backend PHP sur `http://localhost:8010`, et le frontend Vite sur
`http://localhost:5200`.

### Installation manuelle

**Backend**
```bash
cd backend
composer install
cp .env.example .env   # ajuster JWT_SECRET, DB_*, MAIL_* si besoin
php database/migrate.php
php -S localhost:8010 -t public/
```

**Frontend**
```bash
cd frontend
npm install
npm run dev
```

## 🌍 Déploiement

La mise en ligne se fait en conteneurs (PHP-FPM + nginx) derrière Traefik, sur une instance MySQL
mutualisée avec le projet PHP jumeau — voir **[DEPLOIEMENT.md](DEPLOIEMENT.md)** pour la procédure
complète.

```bash
cp .env.docker.example .env.docker    # puis remplir les secrets
docker compose --env-file .env.docker up -d --build
```

En production, nginx sert le SPA **et** transmet `/api` à php-fpm : front et API partagent une
seule origine. `scripts/start-dev.ps1` ne concerne que le développement local et n'est pas
embarqué dans les images.

## 🔐 Authentification

JWT access token (courte durée, en mémoire côté client) + refresh token rotatif dans un cookie httpOnly —
identique au projet PHP jumeau.

## 🧑‍💻 Exécution du code des exercices

Contrairement au projet PHP jumeau (sandbox serveur via `proc_open`), le code JS soumis par l'apprenant
s'exécute **dans le navigateur**, dans un Web Worker isolé (`frontend/src/utils/jsSandbox.js`), avec un
timeout de 5s. La sortie de référence de chaque exercice (`expected_output`) est précalculée une fois par
l'admin via le bouton « Tester la solution » du panneau `/admin`, dans ce même sandbox.

## 📝 License

Ce projet est développé dans un cadre éducatif.
