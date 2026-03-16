# Chirper Workspace Instructions

Use these instructions for all tasks in this repository.

## Tech Stack

- Laravel 12 + PHP 8.4 runtime in Docker (`php-fpm`), PostgreSQL, Nginx.
- Blade views + Tailwind CSS + DaisyUI components.
- Vite handles frontend assets.
- Tests use Pest.

## Preferred Commands

- Full local dev loop: `composer run dev`
- First-time setup: `composer run setup`
- Run tests: `composer run test`
- Frontend dev: `npm run dev`
- Frontend production build: `npm run build`
- Apply migrations: `php artisan migrate`
- Seed database: `php artisan db:seed`

Prefer `composer run dev` over manually running multiple processes.

## Architecture Map

- Routes: `routes/web.php`
- Main CRUD controller: `app/Http/Controllers/ChirpController.php`
- Auth controllers: `app/Http/Controllers/Auth/`
- Models: `app/Models/User.php`, `app/Models/Chirp.php`
- Authorization: `app/Policies/ChirpPolicy.php`
- Views: `resources/views/` (notably `home.blade.php` and `components/`)
- Migrations: `database/migrations/`
- Seeders: `database/seeders/DatabaseSeeder.php`
- Dev containers: `compose.yaml`
- Production containers: `compose.prod.yaml`
- Deployment notes: `docs/deployment-registry.md`

## Coding Conventions

- Follow existing Laravel conventions and keep changes minimal/surgical.
- Use Pest style for tests; add/adjust tests when behavior changes.
- Keep validation close to controller actions unless project patterns indicate extraction.
- Respect policy-based authorization (`ChirpPolicy`) for update/delete ownership checks.
- Reuse Blade components (`<x-layout>`, `<x-chirp>`) rather than duplicating markup.

## Environment and Runtime Gotchas

- PostgreSQL is required in Docker workflows; ensure DB env vars are set.
- In production, `APP_ENV=production` and `APP_DEBUG=false` must be set in VM `.env`.
- Run migrations before relying on database-backed sessions/cache.
- If UI changes do not appear, run `npm run dev` or rebuild with `npm run build`.
- Nginx production domain is currently configured in `docker/nginx/default.conf`; adjust for new domains.
- `compose.prod.yaml` expects pre-built registry images and DB health checks.

## Deployment Notes

- Production deployment is via GitHub Actions workflow that builds/pushes Docker images and deploys over SSH.
- Required repository secrets include Docker Hub credentials and VM SSH details.
- Use non-interactive commands/scripts suitable for CI/CD.

## Agent Behavior Expectations

- Before editing, inspect related files and preserve existing style.
- Do not introduce broad refactors unless explicitly requested.
- Prefer `rg`/`rg --files` for search.
- After edits, run relevant tests or targeted verification commands when possible.
- If `GEMINI.md` imposes stricter workflow/tooling guidance for a task, follow it.
