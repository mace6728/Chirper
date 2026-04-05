# Registry Image Deployment Guide

This project deploys using prebuilt Docker Hub images and a pull-based rollout on the VM.

## 1. GitHub repository secrets

Add the following repository secrets:

- `DOCKER_USERNAME`: Docker Hub username
- `DOCKER_PASSWORD`: Docker Hub access token/password
- `ENV_PRODUCTION`: Full multiline content of production `.env.production`

This deployment flow assumes the `deploy` job runs on a self-hosted runner installed on your VM.

## 2. VM prerequisites

On the VM, ensure these are installed:

- Docker Engine + Docker Compose v2
- Git
- A valid Let's Encrypt certificate for `www.sdc-cloud.me` and `sdc-cloud.me`
- GitHub Actions self-hosted runner service (Linux x64 label)
- Self-hosted runner service user must have Docker access (typically in `docker` group)

Quick preflight check on VM (as runner user):

```bash
docker --version
docker compose version
id
```

If the runner user is not in the `docker` group, add it and restart runner service.

Clone the project to `~/Chirper`:

```bash
git clone <your-repo-url> ~/Chirper
cd ~/Chirper
```

Create and configure a dedicated production env file at `~/Chirper/.env.production`.
Set all required production variables directly in that file.

If you deploy via GitHub Actions, this file is generated from the `ENV_PRODUCTION` secret at runtime.

For manual local compose commands on VM, export image coordinates first:

```bash
export DOCKER_USERNAME=<your-dockerhub-username>
export IMAGE_TAG=<immutable-tag-or-sha>
```

In CI deploy runs, these two variables are injected by the workflow automatically.
Generate your app key with:

```bash
docker compose --env-file .env.production -f compose.prod.yaml run --rm app php artisan key:generate --show
```

Copy the output to `APP_KEY=` in `.env.production`.

At minimum:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY=<generated key>`
- `DB_CONNECTION=pgsql`
- `DB_HOST=pgsql`
- `DB_PORT=5432`
- `DB_DATABASE=<your-db-name>`
- `DB_USERNAME=<your-db-user>`
- `DB_PASSWORD=<your-db-password>`
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `APP_URL=https://sdc-cloud.me`

## 3. CI/CD behavior

Workflow file: `.github/workflows/deploy.yaml`

- On push to `main`:
  - Build app and nginx images using production Dockerfiles.
  - Push tags: `latest` and `${GITHUB_SHA}`.
  - Deploy `${GITHUB_SHA}` directly on self-hosted VM runner with `compose.prod.yaml` and `--env-file .env.production`.
  - Run `php artisan migrate --force`.
- Manual deploy (`workflow_dispatch`):
  - Deploy any tag by setting `image_tag` (for rollback/redeploy).

## 4. Rollback

In GitHub Actions, run workflow manually with:

- `image_tag=<previous_sha>`

The workflow will pull that tag and redeploy.

## 5. Notes

- `compose.yaml` remains for local development.
- `compose.prod.yaml` is for VM deployment only.
- VM deployment reads environment variables from `.env.production`.
- CI injects `DOCKER_USERNAME` and `IMAGE_TAG` at runtime; keep `.env.production` focused on app/database/runtime settings.
- `compose.prod.yaml` maps `80:80` and `443:443` for HTTP + HTTPS.
- TLS certs are mounted from VM `/etc/letsencrypt` to container `/etc/ssl/letsencrypt` (read-only).
- Development Nginx uses `docker/nginx/default.conf` (HTTP only); production image uses `docker/nginx/default.prod.conf` (TLS + redirect).
- Current setup assumes public Docker Hub repositories.
- PostgreSQL data is persisted in the `sail-pgsql` volume. If DB credentials are changed in `ENV_PRODUCTION`, deployment now syncs the database role password before app startup.
