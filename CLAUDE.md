# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a learning platform for teaching **JavaScript** relatively comprehensively — language fundamentals, loops/collections, functions, strings/dates, the DOM and events, asynchronous JS, object-oriented programming, modern JS/ES6+, and a capstone project. OOP is one module among nine (see `ROADMAP.md`), not the organizing theme of the curriculum. Architecturally, it is still the JS-flavored sibling of `apprentissage-POO-PHP` (same directory tree up one level: `C:\Dev\apprentissage-POO-PHP`), built by cloning that project's architecture wholesale and swapping only what genuinely differs between the two languages — but the *content* curriculum has diverged from the PHP sibling's OOP-centric shape (see "Course Content Structure" below). The project consists of:
- A **backend API** built with PHP 8.2+ using the exact same hand-rolled MVC architecture as the PHP sibling project (custom `Router` in `backend/src/Helpers/Router.php`) — the backend is still PHP, it just serves a JavaScript curriculum instead of a PHP one
- A **frontend** built with React 19 + Vite
- A **MySQL database** (`apprentissage_js`) for storing course content, user progress, and gamification data — shares the same MySQL server instance as the PHP sibling project, just a different database name

**Course content is currently empty.** Unlike the PHP sibling project (which has 70 theories / 51 exercises already written), no modules/chapitres/theories/exercices are seeded here — the whole JavaScript curriculum is meant to be authored from scratch via the `/admin` panel, exactly the way the PHP project's own content was originally authored.

## The one architectural difference that matters: code execution

The PHP sibling project runs submitted student code **server-side**, as a PHP CLI subprocess (`backend/src/Services/CodeExecutionService.php`, via `proc_open`). That approach doesn't translate to JavaScript — there's no server-side JS engine here. Instead:

- **Student code execution happens entirely client-side**, in the learner's own browser, via a dedicated Web Worker sandbox: `frontend/src/utils/jsSandbox.js` (`runJs(code)`). A Worker is used (not an iframe) specifically because `worker.terminate()` reliably kills a synchronous infinite loop, whereas removing an iframe does not guarantee that.
- The Worker's `console.log/info/warn/error` are captured into a single output string; thrown errors are caught and returned as `"ErrorName: message"`; a 5-second timeout mirrors the PHP sandbox's `max_execution_time=5`. `fetch`/`XMLHttpRequest`/`importScripts` are disabled inside the worker to keep the same "no network" spirit as the PHP sandbox's `open_basedir`/`allow_url_fopen=0`.
- Because there's no server-side JS engine, the "reference solution" output can't be recomputed on every submission the way the PHP backend re-runs `solution_code` each time. Instead, `exercices.expected_output` (a new column that doesn't exist in the PHP sibling's schema) stores a **precomputed** reference output. It's filled in by the admin, in `ExerciceManager.jsx`, via a "▶️ Tester la solution" button that runs `solution_code` through the exact same `jsSandbox.js` used by students.
- `POST /api/exercices/{id}/submit` therefore has a different contract than in the PHP project: the frontend runs the student's code locally *first* (via `runJs`), then POSTs the already-captured `{code, output, error, timed_out, time_spent}` to the backend. `ExerciceController::submit()` no longer executes anything — it only compares `output` against the stored `expected_output` (same whitespace-insensitive, lowercased comparison logic as the PHP sibling: `normalizeOutput()`), then does the same bookkeeping (attempt counting, points, progression, gamification) as before.
- `CodeExecutionService.php` does not exist in this project at all — there is nothing to replace it with server-side, by design.

Everything else — auth, gamification, admin CRUD, the `modules → chapitres → theories/exercices` content model — was copied over essentially unchanged from the PHP sibling project. If you're looking for prior art on any backend service (auth, rate limiting, refresh tokens, email verification, password reset, mailer, progression, badges/points/streaks), check the equivalent file in `C:\Dev\apprentissage-POO-PHP\backend\src\` first — it's very likely byte-for-byte identical or nearly so.

## Starting the dev environment

Use `scripts/start-dev.ps1` (PowerShell) instead of starting services manually:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start-dev.ps1
```

Same gotchas as the PHP sibling project (same machine, same caveats):
- The system `php` in PATH is 7.4 (incompatible — this project requires PHP ≥ 8.2). The script pins the binary to `C:\wamp64\bin\php\php8.2.26\php.exe`.
- The `wampmysqld64` Windows service is not always running, and starting it needs admin rights the script doesn't have — it falls back to launching `mysqld.exe` manually (`C:\wamp64\bin\mysql\mysql9.1.0\bin\mysqld.exe`), logged to `backend/mysql-manual.log`.

**Ports are intentionally different from the PHP sibling project** so both can run simultaneously without collision:
- Backend: `http://localhost:8010` (PHP project uses 8000)
- Frontend: `http://localhost:5200` (PHP project uses 5173–5190)
- MySQL: `3306`, shared with the PHP project — `apprentissage_js` and `apprentissage_poo` are two separate databases in the same MySQL instance.

## Project Structure

### Backend (`/backend`)
Identical layout to the PHP sibling project:
```
/src
  /Config        - Database.php, Cors.php, JwtConfig.php
  /Controllers   - Auth, Module, Chapitre, Theorie, Exercice, Dashboard, Gamification
  /Models        - User, Module, Chapitre, Theorie, Exercice (Active Record style)
                   Exercice has one extra field vs. the PHP project: expected_output
  /Services      - Auth, RefreshToken, RateLimit, EmailVerification, PasswordReset,
                    Mailer, Progression, Gamification
                    (no CodeExecutionService — see "code execution" above)
  /Middleware    - AuthMiddleware.php
  /Helpers       - Router.php (custom routing), Response.php
/public
  index.php      - Application entry point; all routes registered here directly
/database
  schema.sql     - Full schema (consolidated — includes what, in the PHP sibling
                   project, was added later via add_password_reset.sql and
                   add_auth_hardening.sql) + badges seed data only (no course content)
  migrate.php    - Runs schema.sql (single file, no content seed to layer on top)
```

### Frontend (`/frontend`)
Identical layout to the PHP sibling project, with these JS-specific additions/renames:
```
/src
  /utils
    jsSandbox.js         - Client-side JS execution (Web Worker), see above.
                            Equivalent of the PHP project's server-side CodeExecutionService.
    jsErrorTranslator.js - Friendly French explanations for common JS errors
                            (ReferenceError, TypeError, SyntaxError, RangeError...).
                            Renamed/rewritten from the PHP project's phpErrorTranslator.js.
  /components/content
    JsCheatSheet.jsx     - JS syntax reference overlay, organized by section (Bases,
                            Classe, Héritage, Fonctions & modules...). Renamed/rewritten
                            from PhpCheatSheet.jsx.
    ExerciceSolver.jsx   - Reworked: runs student code locally via jsSandbox.js before
                            submitting the captured result to the backend (see above).
  /components/admin
    ExerciceManager.jsx  - Has an added expected_output field + "Tester la solution"
                            button (runs solution_code through jsSandbox.js to
                            precompute the reference output — admin-time only).
```

## Course Content Structure

**Empty by design.** No modules/chapitres/theories/exercices are seeded. The intended curriculum shape — **not** a copy of the PHP sibling's OOP-centric one — is planned in `ROADMAP.md` at the repo root: 9 modules / 27 chapters covering language basics, loops/collections, functions, strings/dates, the DOM and events, asynchronous JS, OOP (module 7 of 9 — one topic among several, not the spine of the curriculum), modern JS/ES6+, and a capstone project. `ROADMAP.md` also proposes, per chapter, a *Guidé*/*Défi* exercise pair and a *Concept*/*Application* illustration pair. It's a working plan, not fixed content — modules/chapitres are still created and ordered entirely through `/admin`, and the plan can be freely reshaped during authoring.

## Database Schema

Same core tables as the PHP sibling project — `users`, `modules`, `chapitres`, `theories`, `exercices`, `progression`, `theorie_lue`, `exercice_soumis`, `badges`, `user_badges`, `points`, `streaks`, `quiz`/`questions` (reserved, unused), `refresh_tokens`, `login_history` — with two differences:
- `exercices.expected_output` (new column, see "code execution" above).
- The auth-hardening columns/tables that the PHP project bolted on later via `add_password_reset.sql`/`add_auth_hardening.sql` (and the now-dead `remember_tokens` table it replaced) are folded directly into `schema.sql` here from the start. There's no migration history to replay.

Badges are reused verbatim from the PHP sibling project (the medieval point-tier ladder Manant → Écuyer → Chevalier → Baron → Roi, streak/progression/achievement badges), with PHP/POO-specific flavor text swapped for JavaScript-neutral wording. The one badge that was genuinely PHP/MySQL-specific ("Survivant de PDO") was dropped rather than reskinned.

## Development Commands

### Backend Setup
```bash
composer install
php backend/database/migrate.php   # creates apprentissage_js + schema + badges
php -S localhost:8010 -t public/   # from inside /backend, PHP >= 8.2 required
```

### Frontend Setup
```bash
npm install
npm run dev      # Vite dev server, port 5200 (see vite.config.js)
npm run build
```

## Key Technical Patterns

Copied unchanged from the PHP sibling project unless noted above:
- **Hand-rolled MVC**, no framework. `Router.php` maps routes registered in `public/index.php` to Controllers; Controllers call Services; Services own the SQL via `Database::getConnection()` (PDO, prepared statements).
- **Active Record–style Models** for `User`, `Module`, `Chapitre`, `Theorie`, `Exercice` only.
- **Authentication**: JWT access token (short-lived, in-memory on the frontend) + rotating, hashed, httpOnly-cookie refresh token — identical to the PHP project's `AuthService`/`RefreshTokenService`/`JwtConfig` pattern.
- **Admin panel**: `/admin`, gated by `role === 'admin'`, full CRUD for Modules/Chapitres/Théories/Exercices — identical UI pattern to the PHP project, with the `expected_output` addition on the Exercice form.

## Security Considerations

Same as the PHP sibling project (password hashing, prepared statements, CORS, rate limiting, email verification, input validation), **except** code execution: there is no server-side sandbox to harden here, because there is no server-side execution. The security question for this project's exercise flow is instead "can the submitted JS escape the Worker sandbox and touch the page/app" — the Worker's isolated global scope (no `document`/`window`, no shared memory with the main thread) plus disabling `fetch`/`XMLHttpRequest`/`importScripts` is the mitigation, not `open_basedir`/`disable_functions`.

## Testing

Same as the PHP sibling project: aspirational, not implemented. `phpunit/phpunit` is a dev dependency but no test suite exists yet; no frontend test runner is configured.
