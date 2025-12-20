# How to Move Everything to Public Directory

## Quick Commands for Your Server

Run these commands on your server to move all API files to `public/`:

```bash
cd /var/www/bus-tracking

# Create API directories in public
mkdir -p public/api/auth
mkdir -p public/api/buses
mkdir -p public/api/routes
mkdir -p public/api/gps
mkdir -p public/api/notifications

# Copy all API files
cp -r backend/api/* public/api/

# Move middleware to includes
cp backend/middleware/auth.php includes/middleware.php

# Update paths in all API files
find public/api -name "*.php" -type f -exec sed -i 's|/../../config|/../../../config|g' {} \;
find public/api -name "*.php" -type f -exec sed -i 's|/../../middleware|/../../../includes/middleware|g' {} \;
find public/api -name "*.php" -type f -exec sed -i 's|/../../services|/../../../app/Services|g' {} \;

# Update __DIR__ paths
find public/api -name "*.php" -type f -exec sed -i "s|__DIR__ . '/../../config|__DIR__ . '/../../../config|g" {} \;
find public/api -name "*.php" -type f -exec sed -i "s|__DIR__ . '/../../middleware|__DIR__ . '/../../../includes/middleware|g" {} \;
find public/api -name "*.php" -type f -exec sed -i "s|__DIR__ . '/../../services|__DIR__ . '/../../../app/Services|g" {} \;

echo "✅ Files moved to public/api/"
```

## Or Use the Script

```bash
chmod +x MOVE_TO_PUBLIC.sh
./MOVE_TO_PUBLIC.sh
```

## Update Web Server Configuration

### For Nginx:
```nginx
server {
    root /var/www/bus-tracking/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### For Apache:
Point document root to `/var/www/bus-tracking/public`

## Update JavaScript API Paths

In `public/js/app.js`, change:
```javascript
const API_BASE = '/api';  // Already correct if in public/
```

## Test

After moving, test:
```bash
curl http://your-domain/api/auth/check.php
```

## Final Structure

```
public/
├── api/              # ✅ All API endpoints (web accessible)
├── css/              # ✅ Stylesheets
├── js/               # ✅ JavaScript
├── index.php         # ✅ Main entry
├── login.php         # ✅ Login page
└── dashboard.php     # ✅ Dashboard

config/               # 🔒 Not web accessible (included only)
includes/             # 🔒 Not web accessible (included only)
app/                  # 🔒 Not web accessible (included only)
database/             # 🔒 Not web accessible
```

## Security Note

- ✅ Files in `public/` = Directly accessible via URL
- 🔒 Files outside `public/` = Only accessible via PHP includes (secure)

This is the correct structure!

