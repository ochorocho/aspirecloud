# AspireCloud

AspireCloud is a CDN and API endpoint system for distributing WordPress assets (themes, plugins, core updates). It acts as a proxy and pull-through cache against WordPress.org, serving resources to users of the [AspirePress Updater](https://github.com/aspirepress/updater-plugin) plugin.

## Prerequisites

You need two things installed on your machine:

1. **Docker** — [Install Docker Desktop](https://www.docker.com/products/docker-desktop/)
2. **DDEV** — [Install DDEV](https://ddev.readthedocs.io/en/stable/users/install/)

That's it. Everything else (PHP, Node.js, PostgreSQL, Redis, etc.) runs inside containers.

## Quick Start

```bash
git clone https://github.com/aspirepress/AspireCloud
cd AspireCloud
ddev start
ddev init
```

`ddev init` installs all dependencies, builds frontend assets, sets up the database, and seeds it with sample data. It's safe to run again at any time — it won't break anything.

Once complete, open **https://aspirecloud.ddev.site** in your browser.

## Daily Workflow

### Starting and Stopping

```bash
ddev start          # Start the environment
ddev stop           # Stop (preserves data)
ddev restart        # Restart all containers
```

### Running Tests

```bash
ddev test                         # Run all tests (unit + functional)
ddev test --testsuite=Unit        # Run only unit tests
ddev test --testsuite=Feature     # Run only feature tests
ddev test --filter="test name"    # Run a specific test
```

### Code Quality

```bash
ddev lint           # Run style checks + static analysis
ddev style          # Run code style checks only (Pint)
ddev fix-style      # Auto-fix code style issues
ddev quality        # Run static analysis only (PHPStan)
ddev check          # Run everything: lint + all tests
```

### Frontend Development

```bash
ddev vite           # Start Vite dev server with hot-reload
ddev vite build     # Build production assets
```

The Vite dev server is available at `https://vite.aspirecloud.ddev.site` when running.

## All Available Commands

### Development

| Command | Description |
|---------|-------------|
| `ddev start` | Start the development environment |
| `ddev stop` | Stop the environment (data is preserved) |
| `ddev restart` | Restart all containers |
| `ddev init` | Install dependencies, migrate, seed (safe to re-run) |
| `ddev ssh` | Open a shell inside the web container |
| `ddev vite` | Start Vite dev server with hot-reload |
| `ddev composer ...` | Run Composer commands |
| `ddev exec ...` | Run any command inside the web container |

### Testing

| Command | Description |
|---------|-------------|
| `ddev test` | Run all tests (resets testing DB first) |
| `ddev test --testsuite=Unit` | Run unit tests only |
| `ddev test --testsuite=Feature` | Run feature tests only |
| `ddev check` | Run lint + all tests |

### Code Quality

| Command | Description |
|---------|-------------|
| `ddev lint` | Run style checks + static analysis |
| `ddev style` | Check code style (Pint) |
| `ddev fix-style` | Auto-fix code style (Pint) |
| `ddev quality` | Run PHPStan static analysis |

### Database

| Command | Description |
|---------|-------------|
| `ddev psql` | Open PostgreSQL CLI |
| `ddev reset-database` | Drop and recreate the main database |
| `ddev reset-testing-database` | Drop and recreate the testing database |
| `ddev import-db --file=dump.sql` | Import a database dump |
| `ddev export-db -f backup.sql.gz` | Export the database |

### Debugging

| Command | Description |
|---------|-------------|
| `ddev xdebug on` | Enable Xdebug (for step debugging) |
| `ddev xdebug off` | Disable Xdebug |
| `ddev mailpit` | Open Mailpit (catches all outgoing email) |
| `ddev logs -f` | Follow container logs |
| `ddev describe` | Show project info, URLs, and service status |

### Production

| Command | Description |
|---------|-------------|
| `ddev build-prod` | Build the production Docker image |

## Project Architecture

AspireCloud runs as a set of containers managed by DDEV:

| Service | Purpose |
|---------|---------|
| **Web** (nginx + PHP 8.4) | Serves the Laravel application |
| **PostgreSQL 16** | Primary database |
| **Redis** | Sessions, cache, and queue backend |
| **Elasticsearch 8.15** | Full-text search and indexing |
| **Mailpit** | Catches all outgoing email for testing |
| **Queue Worker** | Processes background jobs |

### Tech Stack

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Vue 3, Inertia.js, Tailwind CSS, Vite
- **Database**: PostgreSQL 16
- **Search**: Elasticsearch 8.15
- **Cache/Queue**: Redis

## Configuration

### Environment Variables

The `.env` file is auto-managed by DDEV for database and mail settings. Key variables you might want to change:

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_URL` | `https://aspirecloud.ddev.site` | Application URL |
| `ELASTICSEARCH_AUTO_INDEX` | `false` | Auto-index on plugin changes |
| `DOWNLOAD_BASE` | `https://aspirecloud.ddev.site/download/` | Base URL for asset downloads |
| `FAIR_REPOS` | *(see .env)* | FAIR repository URLs |
| `QUEUE_CONNECTION` | `redis` | Queue driver (`redis`, `sync`) |

### DDEV Configuration

All DDEV config lives in the `.ddev/` directory:

| File | Purpose |
|------|---------|
| `.ddev/config.yaml` | Main DDEV config (PHP version, DB type, etc.) |
| `.ddev/config.vite.yaml` | Vite dev server integration |
| `.ddev/docker-compose.redis.yaml` | Redis service |
| `.ddev/docker-compose.elasticsearch.yaml` | Elasticsearch service |
| `.ddev/commands/web/*` | Custom DDEV commands |

## Troubleshooting

### `ddev start` fails with port conflict

Another service is using port 80, 443, or 8025. Either stop the conflicting service or configure DDEV to use different ports:

```bash
ddev config --router-http-port=8080 --router-https-port=8443
ddev restart
```

### Database connection errors

Make sure the database container is running:

```bash
ddev describe          # Check service status
ddev restart           # Restart everything
```

If the database is corrupted, reset it:

```bash
ddev reset-database
```

### Tests fail after pulling new changes

Re-run init to update dependencies and migrations:

```bash
ddev init
```

### Elasticsearch errors

Elasticsearch may need time to start. Check its status:

```bash
ddev exec curl -s http://elasticsearch:9200/_cluster/health | jq
```

### Starting completely fresh

If nothing else works, delete everything and start over:

```bash
ddev delete -Oy        # Remove all containers and volumes
rm -rf vendor node_modules
ddev start
ddev init
```

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b my-feature`
3. Make your changes
4. Run the full check: `ddev check`
5. Commit with a clear message
6. Open a pull request

## How It Works

AspireCloud operates as an API and a pseudo pull-through cache against WordPress.org. When a request comes in:

1. If AspireCloud has the requested resource, it serves it directly
2. Otherwise, it passes the request through to WordPress.org and returns their response

The long-term goal is to gradually implement WordPress.org APIs to reduce reliance on their website and endpoints.

**Important**: Please do not use this project to flood or harass the WordPress.org website.

## License

This project is licensed under the [MIT License](https://opensource.org/license/mit).
