# WordPress 7 in Docker — Setup Runbook for Claude Code

> **Purpose:** This is an executable runbook. Follow each step in order. After every step there is a **Verify** checkpoint — do not continue until it passes. If a checkpoint fails, jump to the matching entry in [Troubleshooting](#troubleshooting).
>
> **Target:** A local WordPress 7.0 ("Armstrong", released May 20, 2026) site running in Docker with a MySQL database, reachable at `http://localhost:8080`.
>
> **Assumptions:** A Unix-like shell (macOS, Linux, or WSL2). Adjust paths for native Windows PowerShell if needed.

---

## Step 0 — Preconditions

Confirm Docker and the Compose plugin are installed and the daemon is running.

```bash
docker --version
docker compose version
docker info >/dev/null 2>&1 && echo "Docker daemon: OK" || echo "Docker daemon: NOT RUNNING"
```

**Verify:** Both version commands print a version string, and the last line prints `Docker daemon: OK`.

- If `docker` is not found → install Docker Desktop (macOS/Windows) or Docker Engine (Linux), then re-run.
- If the daemon is not running → start Docker Desktop, or `sudo systemctl start docker` on Linux, then re-run.

---

## Step 1 — Create the project directory

```bash
mkdir -p wordpress-7-docker && cd wordpress-7-docker
pwd
```

**Verify:** `pwd` ends with `/wordpress-7-docker`. All remaining steps run from inside this directory.

---

## Step 2 — Create the environment file

Secrets live in `.env` so they are not hard-coded in the compose file.

```bash
cat > .env << 'EOF'
# Database
MYSQL_ROOT_PASSWORD=change_me_root
MYSQL_DATABASE=wordpress
MYSQL_USER=wp_user
MYSQL_PASSWORD=change_me_wp

# Host port WordPress is served on
WP_PORT=8080
EOF
echo "--- .env created ---"
cat .env
```

> **Action for Claude Code:** Replace `change_me_root` and `change_me_wp` with strong, distinct values before deploying anywhere non-local. For a throwaway local site the defaults are acceptable.

**Verify:** `.env` exists and prints the five variables above.

---

## Step 3 — Create `docker-compose.yml`

```bash
cat > docker-compose.yml << 'EOF'
services:
  db:
    image: mysql:8.4
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${MYSQL_DATABASE}
      MYSQL_USER: ${MYSQL_USER}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-p${MYSQL_ROOT_PASSWORD}"]
      interval: 10s
      timeout: 5s
      retries: 10

  wordpress:
    # WordPress 7.0 recommends PHP 8.3+. Pin the major version, ride patch updates.
    # If the 7.0 tag is unavailable for this variant, swap to: wordpress:latest
    image: wordpress:7.0-php8.4-apache
    restart: unless-stopped
    ports:
      - "${WP_PORT}:80"
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_USER: ${MYSQL_USER}
      WORDPRESS_DB_PASSWORD: ${MYSQL_PASSWORD}
      WORDPRESS_DB_NAME: ${MYSQL_DATABASE}
    volumes:
      - wp_data:/var/www/html
    depends_on:
      db:
        condition: service_healthy

volumes:
  db_data:
  wp_data:
EOF
echo "--- docker-compose.yml created ---"
```

**Verify:** Run `docker compose config` — it should print a fully-resolved config with no errors and the `.env` values substituted in. An error here means a YAML or variable problem; fix before continuing.

---

## Step 4 — Confirm the WordPress 7.0 image tag exists

The pinned tag `wordpress:7.0-php8.4-apache` is published by the Docker maintainers shortly after each release. Confirm it is pullable; if not, fall back to `wordpress:latest`.

```bash
docker pull wordpress:7.0-php8.4-apache && echo "TAG_OK" || echo "TAG_MISSING"
```

**Verify:**
- Prints `TAG_OK` → continue to Step 5.
- Prints `TAG_MISSING` → the specific tag isn't published for this variant yet. Edit `docker-compose.yml` and change the image line to `image: wordpress:latest` (which points at the newest stable release), then re-run this step's pull on `wordpress:latest`.

> **Note:** You can browse valid tags at https://hub.docker.com/_/wordpress . Tags follow the pattern `<version>-php<x.y>-<apache|fpm>`, e.g. `7.0-php8.3-fpm`.

---

## Step 5 — Start the stack

```bash
docker compose up -d
```

This pulls images (first run only) and starts both containers in the background.

**Verify:**

```bash
docker compose ps
```

Both `db` and `wordpress` services should show state `running`. The `db` service should additionally show `healthy`. If `db` is still `starting`, wait ~20s and re-check.

---

## Step 6 — Wait for WordPress to respond

```bash
# Poll the install endpoint until it returns HTTP 200 or 302
for i in $(seq 1 30); do
  code=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/wp-admin/install.php)
  echo "attempt $i: HTTP $code"
  if [ "$code" = "200" ] || [ "$code" = "302" ]; then echo "WORDPRESS_READY"; break; fi
  sleep 3
done
```

**Verify:** The loop prints `WORDPRESS_READY`. If it never does, see [Troubleshooting](#troubleshooting).

---

## Step 7 — Complete the install

Two options — pick one.

### Option A: Browser wizard (simplest)

Open `http://localhost:8080` in a browser and complete the 5-field setup form (site title, admin username, password, email, language). Done.

### Option B: WP-CLI, fully scripted (no browser, ideal for automation)

Run the official WP-CLI image against the already-running containers:

```bash
# Resolve the running WordPress container name
WP_CONTAINER=$(docker compose ps -q wordpress)

docker run --rm \
  --network "$(docker inspect -f '{{range $k,$v := .NetworkSettings.Networks}}{{$k}}{{end}}' "$WP_CONTAINER")" \
  --volumes-from "$WP_CONTAINER" \
  -w /var/www/html \
  wordpress:cli wp core install \
    --url="http://localhost:8080" \
    --title="My WP7 Site" \
    --admin_user="admin" \
    --admin_password="change_me_admin" \
    --admin_email="admin@example.com" \
    --skip-email
```

**Verify (either option):**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/
```

Returns `200`. Visiting `http://localhost:8080/wp-admin/` should now show the login screen (or the dashboard if logged in).

---

## Step 8 — Confirm the version is actually 7.0

```bash
WP_CONTAINER=$(docker compose ps -q wordpress)
docker exec "$WP_CONTAINER" sh -c 'grep "wp_version =" /var/www/html/wp-includes/version.php'
```

**Verify:** The printed line shows a `7.0` version string (e.g. `$wp_version = '7.0';`). If it shows `6.x`, you are on the fallback `latest` from before 7.0 propagated, or an older pinned tag — recheck Step 4.

---

## Daily operations

```bash
# View live logs (Ctrl-C to stop following)
docker compose logs -f wordpress

# Stop the stack (keeps data)
docker compose stop

# Start it again
docker compose start

# Restart after editing docker-compose.yml
docker compose up -d
```

---

## Persisting files for direct editing (optional)

The runbook above keeps WordPress files in a named volume (`wp_data`). To edit theme/plugin files directly from the host instead, replace the wordpress volume mapping with a bind mount:

```yaml
    volumes:
      - ./wp-content:/var/www/html/wp-content
```

Then `docker compose up -d` again. Your themes and plugins now live in `./wp-content` on the host. Leave the rest of WordPress core in the image to avoid permission headaches.

---

## Cleanup / teardown

```bash
# Stop and remove containers + network, KEEP data volumes
docker compose down

# Stop and remove EVERYTHING including the database and WP files (destructive)
docker compose down -v
```

**Verify:** After `down -v`, `docker volume ls` no longer lists `wordpress-7-docker_db_data` or `wordpress-7-docker_wp_data`.

---

## Troubleshooting

**`docker compose` not found, but `docker-compose` works**
You have the legacy standalone binary. Substitute `docker-compose` (with hyphen) for every `docker compose` command, or install the modern Compose plugin.

**Port 8080 already in use**
Another process owns the port. Change `WP_PORT` in `.env` to e.g. `8888`, then `docker compose up -d`. Reach the site at the new port.

**`db` container restarts in a loop / "Access denied"**
Usually a stale database volume from a previous run with different credentials. Reset it: `docker compose down -v` then `docker compose up -d`. This deletes existing data.

**WordPress shows "Error establishing a database connection"**
The DB wasn't ready when WordPress started. The healthcheck + `depends_on` should prevent this, but if it happens: `docker compose restart wordpress`, then re-run Step 6. Also confirm `WORDPRESS_DB_HOST` is `db:3306` (the service name, not `localhost`).

**Step 6 never prints `WORDPRESS_READY`**
Check logs: `docker compose logs wordpress`. Confirm the container is `running` in `docker compose ps`. Confirm nothing else occupies the host port. On Apple Silicon, the `mysql:8.4` image is multi-arch and should run natively; if you see platform warnings, add `platform: linux/arm64` (or `linux/amd64`) under the `db` service.

**Want MariaDB instead of MySQL**
Swap the `db` image to `mariadb:11` and change the healthcheck test to `["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]`. Everything else stays the same.

---

## What WordPress 7.0 changes (context, not setup)

7.0 "Armstrong" is a large release: it introduces native AI infrastructure in core (an AI Client, an Abilities API, and a Connectors hub), a redesigned admin dashboard with a Command Palette, and new design tools and blocks. The much-publicized real-time collaboration feature was postponed to a later version and is **not** in 7.0. None of this affects the Docker setup, but if you are migrating an existing site, test plugins and themes against this disposable container before upgrading production. WordPress 7.0 recommends PHP 8.3 or newer, which is why the image tags above target PHP 8.3/8.4.


Admin user: admin
Admin psw: 6(IJ7#ql7!R9u54A99