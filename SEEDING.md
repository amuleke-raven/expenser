# Database Seeding in Docker

## Overview

The Docker container automatically runs database seeders on initial deployment to populate the application with test data. This is controlled via environment variables.

## How It Works

1. **First Deployment**: On first container start, migrations run followed by database seeders
2. **Subsequent Deployments**: Seeders are skipped (flag file prevents re-seeding)
3. **Force Reseed**: Set `DB_SEED_FORCE=true` to reseed on next deployment

## Environment Variables

### `DB_SEED` (Default: auto)
Controls whether database seeding is enabled.

- **Default for production** (`APP_ENV=production`): `false` (disabled)
- **Default for development** (`APP_ENV=local/staging`): `true` (enabled)
- **Override**: Set explicitly to `true` or `false`

```bash
# Enable seeding
DB_SEED=true

# Disable seeding
DB_SEED=false
```

### `DB_SEED_FORCE` (Default: false)
Forces reseeding even if database was previously seeded.

```bash
# Force reseed on next deployment
DB_SEED_FORCE=true
```

### `DB_SEEDER_CLASS` (Optional)
Run a specific seeder class instead of DatabaseSeeder.

```bash
# Run only UserSeeder
DB_SEEDER_CLASS=UserSeeder

# Run only RewardTypeSeeder
DB_SEEDER_CLASS=RewardTypeSeeder
```

## Usage Examples

### Initial Deployment with Test Data

**docker-compose.yml:**
```yaml
services:
  app:
    environment:
      DB_SEED: "true"
```

**Or via .env:**
```env
DB_SEED=true
```

**Deploy:**
```bash
docker-compose up -d
```

Result: Database seeded with test data from `database/seeders/DatabaseSeeder.php`

---

### Development with Automatic Seeding

Use the development compose file which enables seeding by default:

```bash
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up
```

The `docker-compose.dev.yml` sets:
- `DB_SEED=true` (enabled)
- `APP_ENV=local` (development mode)

---

### Production Deployment (No Seeding)

**docker-compose.yml:**
```yaml
services:
  app:
    environment:
      APP_ENV: production
      DB_SEED: "false"  # Explicitly disabled
```

Or rely on defaults (production = no seeding):

```bash
APP_ENV=production docker-compose up -d
```

---

### Force Reseed Existing Database

To reseed an already-seeded database:

**Option 1: Environment Variable**
```bash
DB_SEED_FORCE=true docker-compose up -d
```

**Option 2: Delete Flag File**
```bash
docker-compose exec app rm /var/www/html/storage/.db_seeded
docker-compose restart app
```

**Option 3: Update docker-compose.yml**
```yaml
services:
  app:
    environment:
      DB_SEED: "true"
      DB_SEED_FORCE: "true"
```

Then:
```bash
docker-compose up -d
```

---

### Run Specific Seeder Only

```bash
DB_SEED=true DB_SEEDER_CLASS=UserSeeder docker-compose up -d
```

Or in docker-compose.yml:
```yaml
services:
  app:
    environment:
      DB_SEED: "true"
      DB_SEEDER_CLASS: "RewardTypeSeeder"
```

---

## Checking Seed Status

### Check if database was seeded:
```bash
docker-compose exec app ls -la /var/www/html/storage/.db_seeded
```

If file exists, database has been seeded.

### View seeding logs:
```bash
docker-compose logs app | grep -i seed
```

Expected output:
```
app_1  | === Seeding database with test data ===
app_1  | Running all seeders (DatabaseSeeder)
app_1  | ✓ Database seeded successfully (flag created: /var/www/html/storage/.db_seeded)
```

---

## Available Seeders

Check `database/seeders/DatabaseSeeder.php` for the list of seeders that run:

```php
$this->call([
    CountrySeeder::class,
    CurrencySeeder::class,
    ProjectSeeder::class,
    RolesAndPermissionsSeeder::class,
    UserSeeder::class,
    PaymentMethodSeeder::class,
    ExpenseGroupSeeder::class,
    WorkflowSeeder::class,
    RewardTypeSeeder::class,
    TicketConfigSeeder::class,
]);
```

---

## Troubleshooting

### Seeders not running

**Check environment variables:**
```bash
docker-compose exec app env | grep DB_SEED
```

**Check logs:**
```bash
docker-compose logs app | grep -A5 -B5 seed
```

**Check APP_ENV:**
```bash
docker-compose exec app env | grep APP_ENV
```

If `APP_ENV=production` and `DB_SEED` is not explicitly set, seeding is disabled by default.

---

### Seeders running on every restart

This shouldn't happen. Check if flag file is being created:

```bash
# Check permissions
docker-compose exec app ls -la /var/www/html/storage/

# Manually create flag to prevent reseeding
docker-compose exec app touch /var/www/html/storage/.db_seeded
```

---

### Need to reset database completely

```bash
# Stop containers
docker-compose down

# Remove database volume
docker volume rm expenser_db_data

# Restart (will reseed)
DB_SEED=true docker-compose up -d
```

---

## Best Practices

### Development
✅ Use `docker-compose.dev.yml` which enables seeding automatically  
✅ Test data available immediately  
✅ Can force reseed anytime with `DB_SEED_FORCE=true`

### Staging
✅ Enable seeding for testing: `DB_SEED=true`  
✅ Use production-like data via custom seeders  
✅ Document seeding requirements

### Production
❌ **Never enable automatic seeding** (`DB_SEED=false`)  
✅ Use migrations only  
✅ Import production data manually  
✅ Use database backups for restore

---

## Examples by Environment

### Local Development
```bash
# First time setup
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Database seeded automatically with test data
# - Test users created
# - Reward types created
# - Sample projects created
```

### CI/CD Pipeline (Staging)
```yaml
# .github/workflows/deploy-staging.yml
env:
  APP_ENV: staging
  DB_SEED: "true"
  DB_CONNECTION: mysql
```

### Production Deployment
```yaml
# docker-compose.prod.yml
services:
  app:
    environment:
      APP_ENV: production
      DB_SEED: "false"  # CRITICAL: No seeding in production
      DB_CONNECTION: mysql
```

---

## Manual Seeding

If you prefer to seed manually:

```bash
# Disable automatic seeding
DB_SEED=false docker-compose up -d

# Seed manually when ready
docker-compose exec app php artisan db:seed

# Or specific seeder
docker-compose exec app php artisan db:seed --class=UserSeeder
```

---

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: Deploy

jobs:
  deploy:
    steps:
      - name: Deploy with test data
        env:
          DB_SEED: "true"
          DB_SEED_FORCE: "false"
        run: |
          docker-compose up -d
          docker-compose logs app | grep "Database seeded successfully"
```

### GitLab CI Example

```yaml
deploy:
  script:
    - export DB_SEED=true
    - docker-compose up -d
    - docker-compose exec -T app php artisan db:show
```

---

## Summary

| Scenario | DB_SEED | DB_SEED_FORCE | Result |
|----------|---------|---------------|--------|
| **First deployment (dev)** | `true` | `false` | ✅ Seeds database |
| **First deployment (prod)** | `false` | `false` | ❌ No seeding |
| **Subsequent deployments** | `true` | `false` | ⏭️ Skips (already seeded) |
| **Force reseed** | `true` | `true` | ✅ Reseeds database |
| **Specific seeder** | `true` + `DB_SEEDER_CLASS` | `false` | ✅ Runs specific seeder |

---

For more information, see:
- Database seeders: `database/seeders/`
- Entrypoint script: `docker/entrypoint.sh`
- Docker compose: `docker-compose.yml`
