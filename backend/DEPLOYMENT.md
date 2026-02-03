# Production Deployment Guide

## Pre-Deployment Checklist

- [ ] All tests passing
- [ ] Code reviewed
- [ ] Security audit completed
- [ ] Database backups configured
- [ ] Monitoring set up
- [ ] HTTPS certificates obtained
- [ ] Environment variables secured

## Database Setup

### 1. Create Database

**MySQL:**
```sql
CREATE DATABASE inframind CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'inframind'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON inframind.* TO 'inframind'@'localhost';
FLUSH PRIVILEGES;
```

**PostgreSQL:**
```sql
CREATE DATABASE inframind;
CREATE USER inframind WITH PASSWORD 'strong_password_here';
GRANT ALL PRIVILEGES ON DATABASE inframind TO inframind;
```

### 2. Run Migrations

```bash
php bin/migrate.php
```

## Environment Configuration

Create `.env` with production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_NAME=InfraMind
APP_URL=https://api.inframind.com

# Database (use strong password!)
DB_DRIVER=mysql
DB_HOST=db.inframind.com
DB_PORT=3306
DB_NAME=inframind
DB_USER=inframind
DB_PASSWORD=<strong-generated-password>

# JWT (generate new secret!)
JWT_SECRET=<generate-with-php-r-echo-bin2hex-random-bytes-32>
JWT_ALGORITHM=HS256
JWT_EXPIRATION=86400
JWT_REFRESH_EXPIRATION=604800

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60

# CORS
CORS_ENABLED=true
CORS_ORIGINS=https://inframind.com,https://www.inframind.com

# Logging
LOG_LEVEL=info
LOG_PATH=/var/log/inframind
```

## Web Server Configuration

### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name api.inframind.com;

    ssl_certificate /etc/ssl/certs/inframind.crt;
    ssl_certificate_key /etc/ssl/private/inframind.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /var/www/inframind/backend/public;
    index index.php;

    # Logs
    access_log /var/log/nginx/inframind-access.log;
    error_log /var/log/nginx/inframind-error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name api.inframind.com;
    return 301 https://$server_name$request_uri;
}
```

### Apache

```apache
<VirtualHost *:443>
    ServerName api.inframind.com
    ServerAlias www.api.inframind.com

    DocumentRoot /var/www/inframind/backend/public

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/inframind.crt
    SSLCertificateKeyFile /etc/ssl/private/inframind.key

    <Directory /var/www/inframind/backend/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
    </Directory>

    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"

    ErrorLog ${APACHE_LOG_DIR}/inframind-error.log
    CustomLog ${APACHE_LOG_DIR}/inframind-access.log combined
</VirtualHost>

<VirtualHost *:80>
    ServerName api.inframind.com
    Redirect permanent / https://api.inframind.com/
</VirtualHost>
```

## PHP Configuration

### php-fpm

```ini
; /etc/php/8.2/fpm/pool.d/inframind.conf
[inframind]
user = www-data
group = www-data
listen = 127.0.0.1:9000

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 2
pm.max_spare_servers = 10

; Increase limits for large datasets
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 30
php_admin_value[post_max_size] = 10M
php_admin_value[upload_max_filesize] = 10M
```

### php.ini Settings

```ini
[PHP]
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log

date.timezone = UTC
max_execution_time = 30
memory_limit = 256M
post_max_size = 10M
upload_max_filesize = 10M

[Session]
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = Lax
```

## systemd Service (optional)

```ini
; /etc/systemd/system/inframind-api.service
[Unit]
Description=InfraMind API Service
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/inframind/backend
ExecStart=/usr/bin/php -S 0.0.0.0:8000 -t public
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

## Monitoring & Logging

### Log Rotation

```conf
; /etc/logrotate.d/inframind
/var/log/inframind/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload php-fpm > /dev/null 2>&1 || true
    endscript
}
```

### Health Monitoring

```bash
#!/bin/bash
# /usr/local/bin/check-inframind-health.sh

ENDPOINT="https://api.inframind.com/health"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$ENDPOINT")

if [ "$RESPONSE" != "200" ]; then
    echo "InfraMind API unhealthy: HTTP $RESPONSE"
    # Send alert
    exit 1
fi

exit 0
```

Set up cron:
```bash
*/5 * * * * /usr/local/bin/check-inframind-health.sh
```

### Database Backups

```bash
#!/bin/bash
# /usr/local/bin/backup-inframind.sh

BACKUP_DIR="/backups/inframind"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="inframind"
DB_USER="inframind"

mkdir -p "$BACKUP_DIR"

mysqldump -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" | \
    gzip > "$BACKUP_DIR/inframind_$DATE.sql.gz"

# Keep last 7 days
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +7 -delete
```

Set up cron:
```bash
0 2 * * * /usr/local/bin/backup-inframind.sh
```

## Security Hardening

### 1. File Permissions

```bash
# Set appropriate permissions
chmod 755 /var/www/inframind/backend
chmod 755 /var/www/inframind/backend/public
chmod 700 /var/www/inframind/backend/src
chmod 700 /var/www/inframind/backend/database
chmod 700 /var/www/inframind/backend/bin

# Logs directory
chmod 755 /var/log/inframind
chown www-data:www-data /var/log/inframind

# .env file
chmod 600 /var/www/inframind/backend/.env
chown www-data:www-data /var/www/inframind/backend/.env
```

### 2. Firewall Rules

```bash
# Allow HTTPS only
ufw allow 443/tcp
ufw allow 80/tcp  # For redirect
ufw deny from any to any port 3306  # Block MySQL from external
```

### 3. Fail2Ban

```conf
; /etc/fail2ban/jail.d/inframind.conf
[inframind]
enabled = true
port = http,https
filter = inframind
logpath = /var/log/nginx/inframind-access.log
maxretry = 5
findtime = 600
bantime = 3600
```

## Deployment Process

1. **Backup database**
   ```bash
   /usr/local/bin/backup-inframind.sh
   ```

2. **Pull latest code**
   ```bash
   cd /var/www/inframind/backend
   git pull origin main
   ```

3. **Install dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Run migrations**
   ```bash
   php bin/migrate.php
   ```

5. **Clear caches**
   ```bash
   rm -rf logs/rate_limit/*
   ```

6. **Reload PHP-FPM**
   ```bash
   sudo systemctl reload php8.2-fpm
   ```

7. **Verify health**
   ```bash
   curl https://api.inframind.com/health
   ```

## Performance Tuning

### Database

```sql
-- Add indices if not present
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_analyses_employee ON analyses(employee_id);
CREATE INDEX idx_analyses_status ON analyses(status);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at);

-- Check index usage
ANALYZE TABLE users;
ANALYZE TABLE analyses;
```

### PHP

- Enable opcache
- Set appropriate memory limits
- Use connection pooling
- Enable compression

### Nginx

```nginx
# Gzip compression
gzip on;
gzip_types text/plain text/css application/json application/javascript;
gzip_min_length 1024;

# Buffer settings
client_body_buffer_size 128k;
client_max_body_size 10M;
```

## Monitoring Recommendations

1. **Application Performance**
   - Monitor response times
   - Track error rates
   - Monitor queue depths

2. **Database Performance**
   - Monitor query times
   - Track connection count
   - Monitor disk usage

3. **System Resources**
   - CPU usage
   - Memory usage
   - Disk I/O
   - Network I/O

4. **Security**
   - Failed login attempts
   - Rate limit triggers
   - Unauthorized access attempts

## Scaling Considerations

- Database read replicas for read-heavy workloads
- Caching layer (Redis) for session/rate limit
- Load balancer for horizontal scaling
- Message queue for async operations
- CDN for static assets

## Rollback Plan

If deployment fails:

1. **Restore database backup**
   ```bash
   mysql -u inframind -p inframind < /backups/inframind/inframind_YYYYMMDD.sql
   ```

2. **Revert code**
   ```bash
   git revert <commit>
   git push origin main
   ```

3. **Restart services**
   ```bash
   sudo systemctl restart php8.2-fpm nginx
   ```

---

For questions or issues, see [BACKEND_MIGRATION_GUIDE.md](./BACKEND_MIGRATION_GUIDE.md)
