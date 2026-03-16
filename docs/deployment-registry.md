# Registry Image Deployment Guide

This project deploys using prebuilt Docker Hub images and a pull-based rollout on the VM.

## 1. GitHub repository secrets

Add the following repository secrets:

- `DOCKER_USERNAME`: Docker Hub username
- `DOCKER_PASSWORD`: Docker Hub access token/password
- `VM_IP`: Deployment VM public IP
- `VM_USER`: SSH user on VM
- `VM_SSH_KEY`: Private key content for SSH

## 2. VM prerequisites

On the VM, ensure these are installed:

- Docker Engine + Docker Compose v2
- Git

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
  - Deploy `${GITHUB_SHA}` to VM with `compose.prod.yaml` and `--env-file .env.production`.
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
- Current setup assumes public Docker Hub repositories.
