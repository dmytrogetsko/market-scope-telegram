# 🚀 MarketScope Telegram Bot

**MarketScope** is a SaaS platform for real-time OLX monitoring. The system utilizes a hybrid architecture (Laravel + Python), Redis queues, and AI (OpenAI GPT-4o) to analyze deal value, filter scams, and instantly notify users via Telegram.

## 🛠 Tech Stack

### Backend (Laravel 11)

* **Architecture:** Modular Monolith (`nwidart/laravel-modules`)
* **Database:** PostgreSQL 18
* **Cache & Queues:** Redis
* **Admin Panel:** FilamentPHP
* **Telegram Bot:** `defstudio/telegraph`
* **AI Integration:** `openai-php/laravel`
* **DTOs:** `spatie/laravel-data`
* **Monitoring:** `laravel/horizon`, `opcodesio/log-viewer`

### Scraper Microservice (Python)

* **Core:** Python 3.10
* **Browser Automation:** Playwright (Headless Chromium)
* **Communication:** Redis Pub/Sub & Queues
* **Image Processing:** OpenCV (Blur & Duplicate detection)

---

## ⚙️ Prerequisites

You **do not need** PHP, Composer, or Python installed on your local machine. Everything runs inside isolated Docker containers.

**Requirements:**

* **Docker Desktop** (or Docker Engine on Linux/WSL)
* **Git**

---

## 🚀 Installation & Setup

### 1. Clone Repository

```bash
git clone https://github.com/USERNAME/market-scope-telegram.git
cd market-scope-telegram

```

### 2. Environment Setup

Copy the configuration file:

```bash
cp .env.example .env

```

Open `.env` and configure critical keys:

```ini
OPENAI_API_KEY=sk-proj-...
TELEGRAM_BOT_TOKEN=123456:ABC-DEF...
DB_PASSWORD=secret

```

### 3. Start Containers (Docker Sail)

This command builds the images (Laravel + Python Scraper) and starts them. The first run may take time to download Playwright browsers.

```bash
./vendor/bin/sail up -d --build

```

> **Tip:** It is recommended to add an alias `alias sail='./vendor/bin/sail'` to your `.bashrc` or `.zshrc`. The instructions below assume you use the `sail` command.

### 4. Install Dependencies & Migrate

```bash
# Install PHP dependencies
sail composer install

# Generate App Key
sail artisan key:generate

# Run Database Migrations
sail artisan migrate

# Install Frontend dependencies (for Filament/Vite)
sail npm install
sail npm run build

```

### 5. Verify Status

Check if all services (Laravel, Postgres, Redis, Scraper) are running:

```bash
sail ps

```

You should see 4 containers with status `Up`.

---

## 🖥 Available Services

After running `sail up`:

| Service | URL / Port | Description |
| --- | --- | --- |
| **Web App** | `http://localhost` | Main App / API |
| **Horizon** | `http://localhost/horizon` | Queue Monitoring (Redis) |
| **Log Viewer** | `http://localhost/log-viewer` | Laravel Logs UI |
| **Postgres** | `localhost:5432` | Database |
| **Redis** | `localhost:6379` | Cache & Queues |
| **Mailpit** | `http://localhost:8025` | Email Testing |

---

## 🛡 Git Hooks & Quality Control

We enforce strict code quality and security checks.

### Installing Hooks

To activate hooks locally, run (assuming scripts are in `githooks/`):

```bash
cp githooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit

cp githooks/pre-push .git/hooks/pre-push
chmod +x .git/hooks/pre-push

```

### 1. Pre-commit Hook

Checks run before committing:

* **PHP Syntax Check:** (`php -l`)
* **Strict Types:** Ensures `declare(strict_types=1);` exists in all PHP files.
* **Security:** Prevents committing `.env` files.
* **Gitleaks:** (Optional) Scans for exposed secrets/keys.

### 2. Pre-push Hook

Checks run before pushing (blocks push on failure):

* **PHPStan / Larastan:** Runs static analysis at max level.
* **PHPUnit:** Runs full test suite.

### Requirements for Hooks

```bash
sail composer require --dev phpstan/phpstan nunomaduro/larastan phpunit/phpunit

```

All new PHP files must start with:

```php
<?php
declare(strict_types=1);

```

Example `phpstan.neon`:

```neon
includes:
    - ./vendor/nunomaduro/larastan/extension.neon

parameters:
    level: max
    paths:
        - app
        - database
        - routes

```

---

## 📂 Project Structure

The project is structured as a Monorepo:

```text
market-scope-telegram/
├── app/                 # Laravel Core Logic
├── Modules/             # Domain Modules (Telegram, Scraper, Admin)
├── scraper/             # Python Microservice
│   ├── main.py          # Worker Entry Point
│   ├── Dockerfile       # Playwright Environment
│   └── requirements.txt # Python Dependencies
├── docker-compose.yml   # Container Orchestration
└── ...

```

---

## 📝 IDE Helper (Dev Only)

We use **Laravel IDE Helper** to improve autocomplete and PHPDoc for models and facades.

### Install

```bash
sail composer require --dev barryvdh/laravel-ide-helper
```

### Generate Helper Files

```bash
sail artisan ide-helper:generate   # general helpers
sail artisan ide-helper:meta       # PhpStorm meta
sail artisan ide-helper:models -W  # model PHPDocs
```

> Dev-only; don’t commit `_ide_helper.php` to production. Run after adding models or changing migrations.

---

## 🐞 Troubleshooting

**1. Python Scraper cannot connect to Redis?**
Ensure `scraper/main.py` uses `os.getenv('REDIS_HOST', 'redis')`. Inside the Docker network, services must use service names, not `localhost`.

**2. Permission Denied?**
If running on Linux/WSL, Sail might create files as root. Fix permissions:

```bash
sail root-shell chown -R sail:sail .

```

**3. How to restart the scraper after code changes?**

```bash
sail restart scraper
# Or if requirements.txt changed (needs rebuild):
sail build scraper && sail up -d scraper

```
