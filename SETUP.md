# Professional Setup Guide

This guide will help you set up the School Bus Tracking System with all professional improvements.

## Quick Start

### 1. Prerequisites

- PHP 8.0 or higher
- MySQL/MariaDB 10.3 or higher
- Composer
- Web server (Apache/Nginx)

### 2. Installation Steps

```bash
# Clone the repository
git clone <repository-url>
cd LinuxProject-3

# Install dependencies
composer install

# Create environment file
cp .env.example .env

# Edit .env file with your settings
nano .env  # or use your preferred editor

# Create necessary directories
mkdir -p logs storage/rate_limit storage/cache

# Set permissions
chmod 755 -R .
chmod 777 -R logs storage/

# Create database
mysql -u root -p
CREATE DATABASE school_bus_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Import schema
mysql -u root -p school_bus_tracking < database/schema.sql

# Import seed data (optional)
mysql -u root -p school_bus_tracking < database/seed.sql
```

### 3. Environment Configuration

Edit `.env` file:

```env
# Application Environment
APP_ENV=production          # Use 'development' for local dev
APP_DEBUG=false             # Set to true only in development
APP_URL=http://your-domain.com

# Database Configuration
DB_HOST=localhost
DB_NAME=school_bus_tracking
DB_USER=your_database_user
DB_PASS=your_database_password
DB_CHARSET=utf8mb4

# Security Settings
SESSION_TIMEOUT=3600        # Session timeout in seconds
PASSWORD_MIN_LENGTH=8       # Minimum password length
CSRF_TOKEN_LIFETIME=3600    # CSRF token lifetime

# Google Maps API
GOOGLE_MAPS_API_KEY=your-google-maps-api-key-here

# Mailgun Configuration (Optional)
MAILGUN_DOMAIN=your-domain.mailgun.org
MAILGUN_API_KEY=your-api-key
MAILGUN_FROM_EMAIL=noreply@yourdomain.com

# Timezone
APP_TIMEZONE=Asia/Beirut

# Logging
LOG_LEVEL=error             # Options: debug, info, warning, error, critical
LOG_FILE=logs/app.log

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_REQUESTS=100
RATE_LIMIT_WINDOW=60        # Time window in seconds

# CORS Settings (comma-separated)
CORS_ALLOWED_ORIGINS=http://localhost,http://yourdomain.com
```

### 4. Web Server Configuration

#### Apache (.htaccess)

Create or update `.htaccess` in the `public` directory:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers (if not set in PHP)
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options "nosniff"
</IfModule>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/LinuxProject-3/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
}
```

### 5. Verify Installation

1. **Check PHP version:**
```bash
php -v  # Should be 8.0 or higher
```

2. **Check Composer:**
```bash
composer --version
```

3. **Test database connection:**
Edit `config/database.php` temporarily to test connection, or check logs after first access.

4. **Access the application:**
Navigate to `http://your-domain.com/login.php`

Default credentials:
- Email: `admin@school.com`
- Password: `admin123`

**⚠️ IMPORTANT:** Change default passwords immediately!

### 6. Post-Installation

#### Enable Logging

The system automatically logs to `logs/app.log`. Ensure the directory is writable:

```bash
chmod 777 logs/
```

#### Configure Log Rotation

Add to `/etc/logrotate.d/school-bus-tracking`:

```
/path/to/LinuxProject-3/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
}
```

#### Set Up Cron Jobs (if needed)

For periodic tasks, add to crontab:

```bash
crontab -e
```

Example:
```cron
# Clean rate limit cache daily
0 2 * * * find /path/to/LinuxProject-3/storage/rate_limit -name "*.json" -mtime +1 -delete
```

### 7. Security Checklist

- [ ] `.env` file created and configured
- [ ] `APP_ENV=production` set
- [ ] `APP_DEBUG=false` set
- [ ] Database credentials updated
- [ ] Default passwords changed
- [ ] HTTPS enabled (if available)
- [ ] File permissions set correctly
- [ ] `.env` file not in version control
- [ ] Log directory writable
- [ ] CORS origins configured
- [ ] Rate limiting enabled

### 8. Troubleshooting

#### Database Connection Error
- Check `.env` database credentials
- Verify database exists
- Check MySQL service is running
- Review `logs/app.log` for errors

#### Permission Denied Errors
```bash
chmod 755 -R .
chmod 777 -R logs storage/
```

#### Logs Not Writing
- Check directory permissions
- Verify `logs/` directory exists
- Check PHP error log

#### Environment Variables Not Loading
- Verify `.env` file exists in project root
- Check file permissions on `.env`
- Review `config/env.php` for path issues

### 9. Development vs Production

#### Development Mode
```env
APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=debug
```

#### Production Mode
```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
```

### 10. Next Steps

1. Review `PROFESSIONAL_IMPROVEMENTS.md` for new features
2. Update API documentation if needed
3. Set up monitoring and alerts
4. Configure backups
5. Review security settings
6. Test all functionality
7. Train users on new features

## Support

For issues or questions:
1. Check `PROFESSIONAL_IMPROVEMENTS.md`
2. Review `logs/app.log`
3. Check API documentation
4. Contact system administrator

