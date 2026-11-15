# Testing

## Local quick run (SQLite, fast)

```bash
php -d error_reporting=E_ALL vendor/bin/phpunit
vendor/bin/pint --test app tests
npm run build
```

## Real row-locking concurrency proof (MySQL / MariaDB)

SQLite cannot prove `SELECT ... FOR UPDATE`, so the seller-subscription review
concurrency is verified separately against a real MySQL-compatible engine:

```bash
# Requires MySQL/MariaDB running locally (e.g. XAMPP) on a disposable DB.
$env:DB_CONNECTION='mysql'
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='3307'
$env:DB_DATABASE='alishe_concurrency'
$env:DB_USERNAME='alishe'
$env:DB_PASSWORD='secret'
$env:APP_ENV='local'
php tests/Concurrency/run.php
```

The runner creates a disposable schema (`migrate:fresh`), spawns two PHP worker
processes that wait on a barrier, holds the seller row lock for 2.5s, and asserts
that both workers actually block on the seller row, that exactly one review wins,
and that a seller never ends up with two active subscriptions.

## CI

`.github/workflows/tests.yml` runs:
- `sqlite-suite`: Pint, frontend build, full PHPUnit suite (SQLite).
- `mysql-suite`: full PHPUnit suite on MySQL 8, then the concurrency harness
  against the same MySQL service, so the concurrency evidence is visible on GitHub.

No production database is ever touched; the harness only operates on the
disposable CI/local database.
```