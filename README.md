# opencat-studio

A single-user, browser-based CAT (computer-assisted translation) studio built on the [OpenCAT Framework](https://github.com/shaikhammar/opencat-framework). Handles the full translation workflow — file import, segmentation, TM matching, MT suggestions, terminology highlighting, QA — through a clean Inertia + React UI.

**Stack:** Laravel 13 · PHP 8.3 · Inertia.js v3 · React 19 · Tailwind CSS v4 · Fortify auth · PostgreSQL · Docker

---

## Requirements

- PHP 8.3+
- Composer 2
- Node.js 20+ / npm
- PostgreSQL 16+ with `pg_trgm` extension

---

## Local setup

```bash
# 1. Copy env and create the database
cp .env.example .env
createdb opencat_studio

# 2. Install dependencies (opencat/* packages are pulled from Packagist)
composer install
npm install

# 3. Generate app key and run migrations
php artisan key:generate
php artisan migrate

# 4. Build frontend assets
npm run build

# 5. Start dev server (server + queue + Vite in one command)
composer run dev
```

The studio is available at `http://localhost:8000`.

---

## Docker

```bash
cp .env.example .env
# Fill in APP_KEY (php artisan key:generate prints it)

docker compose up -d
```

Studio at `http://localhost:8000`.

---

## Features

| Area | What's included |
|---|---|
| **File filters** | `.txt`, `.html`, `.docx`, `.xlsx`, `.pptx`, `.po`, `.xml` |
| **Segmentation** | SRX 2.0 rules — EN, HI, UR, AR, FR, DE, ES, ZH, JA bundled |
| **Translation memory** | SQLite TM, exact + fuzzy matching, TMX import/export |
| **Machine translation** | DeepL and Google Translate adapters |
| **Quality assurance** | Tag consistency, number consistency, empty segments, term consistency, cross-segment consistency |
| **Terminology** | TBX v2 glossary import, term recognition with RTL support |
| **Project management** | `.catproject.json` manifest, `.catpack` portable archive |
| **Workflow** | Extract → segment → TM → MT → QA in one orchestrated call |
| **Auth** | Laravel Fortify — registration, login, password reset, email verification |

---

## Configuration

| Env var | Default | Description |
|---------|---------|-------------|
| `DB_CONNECTION` | `pgsql` | Database driver |
| `DB_DATABASE` | `opencat_studio` | Database name |
| `QUEUE_CONNECTION` | `sync` | Use `redis` for production |
| `DEEPL_API_KEY` | — | DeepL MT key |
| `GOOGLE_TRANSLATE_API_KEY` | — | Google Translate key |
| `FILESYSTEM_DISK` | `local` | Set to `s3` for S3-compatible storage |

---

## Running tests

```bash
php artisan test --compact
```

---

## Framework packages

All `opencat/*` packages are available via Composer:

```bash
composer require opencat/workflow opencat/translation-memory opencat/qa
```

See the [OpenCAT Framework](https://github.com/shaikhammar/opencat-framework) for the full package list and documentation.

---

## License

MIT
