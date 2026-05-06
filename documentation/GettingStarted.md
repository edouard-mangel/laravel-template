# Getting Started

Local setup from scratch.

---

## Prerequisites

| Tool | Version | Purpose |
|------|---------|---------|
| PHP | 8.3+ | Runtime |
| Composer | 2.x | PHP package manager |
| Node.js | 22 LTS | JavaScript runtime |
| pnpm | 10.x | JS package manager |
| Docker + Compose | Latest | PostgreSQL, Redis, Mailpit |
| Git | 2.x | Version control |

---

## Step 1: Clone and Configure

```bash
git clone git@gitlab.com:your-org/your-project.git
cd your-project
cp .env.example .env
```

Edit `.env` — set these at minimum:

```env
APP_NAME="Your Project"
APP_KEY=                    # Will be generated in next step
DB_DATABASE=your_project
DB_PASSWORD=secret
SANCTUM_STATEFUL_DOMAINS=localhost:4200
```

---

## Step 2: Install PHP Dependencies

```bash
composer install
php artisan key:generate
```

---

## Step 3: Install Frontend Dependencies

```bash
cd client
pnpm install
cd ..
```

---

## Step 4: Start Infrastructure

```bash
docker compose -f docker/dev/docker-compose.yml up -d
```

This starts:
- **PostgreSQL 17** on port 5432
- **Redis** on port 6379
- **Mailpit** on port 1025 (SMTP) and 8025 (web UI)

Wait ~10 seconds for Postgres to initialize, then verify:

```bash
docker compose -f docker/dev/docker-compose.yml ps
```

---

## Step 5: Set Up the Database

```bash
php artisan migrate
php artisan db:seed --class=DevDataSeeder
```

This creates the schema and seeds development data (users, sample products).

---

## Step 6: Run the Application

Open four terminals (or use a process manager):

```bash
# Terminal 1: API server
php artisan serve

# Terminal 2: Queue worker
php artisan horizon

# Terminal 3: Angular frontend
cd client && pnpm start

# Terminal 4: File watcher (optional)
php artisan queue:listen
```

Access the application:
- **Angular app**: http://localhost:4200
- **API**: http://localhost:8000/api
- **API docs**: http://localhost:8000/api/documentation
- **Horizon**: http://localhost:8000/horizon
- **Telescope**: http://localhost:8000/telescope
- **Mailpit**: http://localhost:8025

---

## Step 7: Verify the Setup

```bash
php artisan test                    # All PHP tests should pass
cd client && pnpm run test:ci       # All frontend tests should pass
```

---

## Development Credentials

Seeded by `DevDataSeeder`:

| Role | Email | Password |
|------|-------|---------|
| Admin | admin@example.com | password |
| Manager | manager@example.com | password |
| Viewer | viewer@example.com | password |

---

## Troubleshooting

**"Connection refused" for database:**
```bash
docker compose -f docker/dev/docker-compose.yml ps  # Is Postgres running?
docker compose -f docker/dev/docker-compose.yml logs postgres
```

**"Migration table not found":**
```bash
php artisan migrate:install
php artisan migrate
```

**Angular fails to start:**
```bash
cd client
rm -rf node_modules
pnpm install
pnpm start
```

**Composer fails with PHP version error:**
```bash
php --version              # Must be 8.3+
composer check-platform-reqs
```

**Queue jobs not processing:**
```bash
php artisan horizon:status    # Is Horizon running?
php artisan queue:failed      # Are there failed jobs?
```
