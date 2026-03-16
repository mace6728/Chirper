# Registry Image Deployment Guide

This project deploys using prebuilt Docker Hub images and a pull-based rollout on the VM.

## 1. GitHub repository secrets

Add the following repository secrets:

- `DOCKER_USERNAME`: Docker Hub username
- `DOCKER_PASSWORD`: Docker Hub access token/password

This deployment flow assumes the `deploy` job runs on a self-hosted runner installed on your VM.

## 2. VM prerequisites

On the VM, ensure these are installed:

- Docker Engine + Docker Compose v2
- Git
- A valid Let's Encrypt certificate for `www.sdc-cloud.me` and `sdc-cloud.me`
- GitHub Actions self-hosted runner service (Linux x64 label)

Clone the project to `~/Chirper`:

```bash
git clone <your-repo-url> ~/Chirper
cd ~/Chirper
```

Create and configure a dedicated production env file at `~/Chirper/.env.production`.

Recommended quick start on VM:

```bash
cd ~/Chirper
cp .env.production.example .env.production
```

Then set all `REPLACE_WITH_*` placeholders.
Set `IMAGE_TAG` to an immutable value (for example a commit SHA), not `latest`.
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
- `compose.prod.yaml` maps `80:80` and `443:443` for HTTP + HTTPS.
- TLS certs are mounted from VM `/etc/letsencrypt` to container `/etc/ssl/letsencrypt` (read-only).
- Development Nginx uses `docker/nginx/default.conf` (HTTP only); production image uses `docker/nginx/default.prod.conf` (TLS + redirect).
- Current setup assumes public Docker Hub repositories.
