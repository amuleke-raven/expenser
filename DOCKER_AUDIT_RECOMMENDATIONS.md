# Docker Setup Audit & Recommended Updates
**Date:** April 30, 2026  
**Application:** RemoteRaven (Expenser)  
**Laravel Version:** 12  
**PHP Version:** 8.3.11

---

## Executive Summary

The current Docker setup is **functional but needs updates** for production readiness. Key areas requiring attention:
- ✅ Good: Multi-stage builds, Alpine-based images, Supervisor process management
- ⚠️ Needs Update: Scheduler, queue worker configuration, Redis support
- 🔧 Recommendations: Security improvements, better caching, database options

---

## Critical Issues (Fix Immediately)

### 1. Missing Laravel Scheduler
**Status:** ❌ CRITICAL  
**Current:** No cron/scheduler running  
**Impact:** Scheduled tasks don't execute (cleanup, reports, notifications)

**Fix:** Add to `docker/supervisord.conf`:
```ini
[program:scheduler]
command=/bin/sh -c "while true; do php /var/www/html/artisan schedule:run --verbose --no-interaction; sleep 60; done"
autostart=true
autorestart=true
priority=20
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

### 2. Queue Worker Not Restarting
**Status:** ❌ CRITICAL  
**Current:** Worker doesn't reload on code changes  
**Impact:** Old code runs indefinitely

**Fix:** Update queue worker in `docker/supervisord.conf`:
```ini
[program:queue]
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --timeout=90 --max-jobs=1000
stopwaitsecs=3600
```

### 3. Database Seeding Logic
**Status:** ❌ CRITICAL  
**Current:** Seeding commented out, no proper first-run detection  
**Impact:** Unpredictable database state

**Fix:** Update `docker/entrypoint.sh`:
```bash
# Seed database only on first run
if [ ! -f /var/www/html/storage/.seeded ]; then
    echo "First run detected - seeding database..."
    php artisan db:seed --force
    touch /var/www/html/storage/.seeded
fi
```

---

## High Priority Improvements

### 4. Add Redis Support
**Status:** ⚠️ HIGH  
**Benefit:** 10-50x faster caching and queue processing

**Update `Dockerfile`** - Add Redis extension:
```dockerfile
# After other PHP extensions
RUN apk add --no-cache pcre-dev \
    && pecl install redis \
    && docker-php-ext-enable redis
```

**Update `docker-compose.yml`** - Add Redis service:
```yaml
services:
  redis:
    image: redis:7-alpine
    restart: unless-stopped
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 3s
      retries: 3

  app:
    depends_on:
      - redis
    environment:
      CACHE_STORE: redis
      QUEUE_CONNECTION: redis
      REDIS_HOST: redis
      REDIS_PORT: 6379

volumes:
  redis_data:
```

### 5. Add MySQL Support
**Status:** ⚠️ HIGH  
**Benefit:** Production-grade database for high-traffic applications

**Update `docker-compose.yml`** - Add MySQL service:
```yaml
services:
  mysql:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-secret}
      MYSQL_DATABASE: ${DB_DATABASE:-expenser}
      MYSQL_USER: ${DB_USERNAME:-expenser}
      MYSQL_PASSWORD: ${DB_PASSWORD:-secret}
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  app:
    depends_on:
      mysql:
        condition: service_healthy
    environment:
      DB_CONNECTION: mysql
      DB_HOST: mysql
      DB_PORT: 3306
      DB_DATABASE: ${DB_DATABASE:-expenser}
      DB_USERNAME: ${DB_USERNAME:-expenser}
      DB_PASSWORD: ${DB_PASSWORD:-secret}

volumes:
  mysql_data:
```

### 6. Enhanced Security Headers
**Status:** ⚠️ HIGH  
**Update `docker/nginx.conf`** - Add comprehensive headers:
```nginx
# Add to server block
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

### 7. Environment-Based OPcache
**Status:** ⚠️ HIGH  
**Update `docker/php.ini`**:
```ini
[opcache]
opcache.enable = 1
opcache.revalidate_freq = ${OPCACHE_REVALIDATE_FREQ:-0}
opcache.validate_timestamps = ${OPCACHE_VALIDATE_TIMESTAMPS:-0}
opcache.max_accelerated_files = 10000
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.fast_shutdown = 1
```

**Update `Dockerfile`** to support env vars in php.ini:
Use a script to substitute environment variables.

---

## Medium Priority Improvements

### 8. Development Docker Compose
**Status:** 🔧 RECOMMENDED  
Create `docker-compose.dev.yml`:
```yaml
services:
  app:
    build:
      target: app
    environment:
      APP_ENV: local
      APP_DEBUG: true
      LOG_LEVEL: debug
      OPCACHE_VALIDATE_TIMESTAMPS: 1
      OPCACHE_REVALIDATE_FREQ: 2
    volumes:
      - .:/var/www/html
    ports:
      - "8183:80"
```

Usage: `docker-compose -f docker-compose.yml -f docker-compose.dev.yml up`

### 9. Configurable Upload Limits
**Status:** 🔧 RECOMMENDED  
**Update `docker/nginx.conf`**:
```nginx
client_max_body_size ${MAX_UPLOAD_SIZE:-64M};
```

**Update `docker/php.ini`**:
```ini
upload_max_filesize = ${MAX_UPLOAD_SIZE:-64M}
post_max_size = ${MAX_UPLOAD_SIZE:-64M}
```

### 10. Add Laravel Horizon (Optional)
**Status:** 🔧 OPTIONAL  
For better queue management and monitoring:

**Install:** `composer require laravel/horizon`

**Update `docker/supervisord.conf`**:
```ini
[program:horizon]
command=php /var/www/html/artisan horizon
autostart=true
autorestart=true
priority=15
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

### 11. Health Check Improvements
**Status:** 🔧 RECOMMENDED  
**Current health check is good**, but consider adding:
```yaml
healthcheck:
  test: ["CMD", "sh", "-c", "wget -qO- http://localhost/up && php artisan queue:health"]
  interval: 30s
  timeout: 10s
  retries: 3
  start_period: 40s
```

### 12. Add Storage Permissions Fix
**Status:** 🔧 RECOMMENDED  
**Update `docker/entrypoint.sh`** - Add at the end:
```bash
# Ensure proper permissions persist
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/storage -type f -exec chmod 664 {} \;
```

---

## Implementation Priority

### Phase 1 (Immediate - This Week)
1. ✅ Add Laravel scheduler to supervisor
2. ✅ Fix queue worker restart behavior
3. ✅ Improve database seeding logic
4. ✅ Add enhanced security headers

### Phase 2 (Short Term - This Month)
1. ✅ Add Redis service and PHP extension
2. ✅ Add MySQL service option
3. ✅ Create development docker-compose
4. ✅ Make upload limits configurable

### Phase 3 (Medium Term - Next Quarter)
1. ✅ Implement Laravel Horizon
2. ✅ Add proper log rotation
3. ✅ Document backup procedures
4. ✅ Set up monitoring/alerting

---

## Testing Checklist

Before deploying updated Docker setup:

- [ ] Build image successfully: `docker-compose build`
- [ ] Container starts without errors: `docker-compose up`
- [ ] Health check passes: `docker-compose ps`
- [ ] Database migrations run: Check logs
- [ ] Seeders execute properly: Check logs
- [ ] Queue worker processes jobs: `php artisan queue:work`
- [ ] Scheduler runs: Check logs every minute
- [ ] File uploads work: Test via UI
- [ ] OPcache works: Check `php -i | grep opcache`
- [ ] Redis connects: `php artisan tinker` then `Cache::get('test')`
- [ ] Application accessible: Visit http://localhost:8183

---

## Additional Recommendations

### Environment Variables Documentation
Create `.env.docker.example`:
```env
# Application
APP_NAME=RemoteRaven
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost:8183
APP_PORT=8183

# Database - SQLite (default)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

# Database - MySQL (alternative)
#DB_CONNECTION=mysql
#DB_HOST=mysql
#DB_PORT=3306
#DB_DATABASE=expenser
#DB_USERNAME=expenser
#DB_PASSWORD=secret

# Cache & Queue - Redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379

# File Uploads
MAX_UPLOAD_SIZE=64M

# OPcache (production: 0, development: 1)
OPCACHE_VALIDATE_TIMESTAMPS=0
OPCACHE_REVALIDATE_FREQ=0

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=hello@remoteraven.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Security Best Practices
1. Never commit `.env` files
2. Use secrets management for production
3. Rotate APP_KEY regularly
4. Use strong database passwords
5. Keep base images updated
6. Run security scans: `docker scan <image>`

### Performance Optimization
1. Enable OPcache in production (`OPCACHE_VALIDATE_TIMESTAMPS=0`)
2. Use Redis for cache and sessions
3. Enable gzip compression (already configured)
4. Use CDN for static assets
5. Monitor container resource usage

---

## Conclusion

**Current Status:** Functional but needs updates for production  
**Risk Level:** Medium (works but not optimized)  
**Estimated Effort:** 4-8 hours for Phase 1 implementation  
**Expected Benefits:** 
- Improved reliability (scheduler, queue restarts)
- Better performance (Redis, OPcache)
- Enhanced security (headers, practices)
- Production readiness

**Next Steps:**
1. Review and approve recommendations
2. Implement Phase 1 (critical fixes)
3. Test thoroughly in staging
4. Deploy to production
5. Monitor and iterate
