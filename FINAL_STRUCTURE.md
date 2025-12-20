# Final Structure - All Web Files in Public

## ✅ Correct Structure

```
/var/www/bus-tracking/
│
├── public/                          # 🌐 WEB ROOT - All accessible files
│   ├── index.php                   # Main entry point
│   ├── login.php                   # Login page
│   ├── dashboard.php               # Dashboard
│   ├── buses.php                   # Bus management
│   ├── routes.php                  # Route management
│   ├── students.php                # Student management
│   ├── users.php                   # User management
│   ├── tracking.php                # Live tracking
│   ├── track-bus.php               # Parent tracking
│   ├── update-location.php         # Driver location update
│   ├── my-bus.php                  # Driver bus info
│   ├── my-children.php             # Parent children
│   ├── logout.php                  # Logout
│   │
│   ├── api/                        # 🌐 API Endpoints (web accessible)
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   ├── logout.php
│   │   │   └── check.php
│   │   ├── buses/
│   │   │   ├── index.php
│   │   │   ├── get.php
│   │   │   ├── create.php
│   │   │   ├── update.php
│   │   │   └── delete.php
│   │   ├── routes/
│   │   │   └── index.php
│   │   ├── gps/
│   │   │   ├── update.php
│   │   │   ├── live.php
│   │   │   └── history.php
│   │   └── notifications/
│   │       ├── index.php
│   │       ├── create.php
│   │       └── mark-read.php
│   │
│   ├── css/                        # 🌐 Stylesheets
│   │   └── main.css
│   │
│   ├── js/                         # 🌐 JavaScript
│   │   ├── app.js
│   │   └── maps.js
│   │
│   ├── assets/                     # 🌐 Images, icons
│   │
│   └── uploads/                    # 🌐 User uploads
│
├── config/                         # 🔒 NOT web accessible (included only)
│   ├── config.php
│   └── database.php
│
├── includes/                       # 🔒 NOT web accessible (included only)
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── middleware.php
│
├── app/                            # 🔒 NOT web accessible (included only)
│   └── Services/
│       ├── AttendanceService.php
│       ├── ReportService.php
│       ├── SMSService.php
│       └── NotificationService.php
│
└── database/                       # 🔒 NOT web accessible
    ├── schema.sql
    ├── schema-v2-enterprise.sql
    └── seed.sql
```

## 🔒 Security

- ✅ Files in `public/` = Directly accessible via URL
- 🔒 Files outside `public/` = Only accessible via PHP `require_once` (secure)

## 📝 Web Server Configuration

### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
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

    # Deny access to files outside public
    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

### Apache
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/bus-tracking/public
    
    <Directory /var/www/bus-tracking/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Deny access to files outside public
    <Directory /var/www/bus-tracking>
        Options -Indexes
        AllowOverride None
        Require all denied
    </Directory>
    <DirectoryMatch "^/var/www/bus-tracking/(config|includes|app|database)">
        Require all denied
    </DirectoryMatch>
</VirtualHost>
```

## 🚀 Quick Migration

Run on your server:
```bash
cd /var/www/bus-tracking
chmod +x COMPLETE_MOVE_SCRIPT.sh
./COMPLETE_MOVE_SCRIPT.sh
```

Or manually:
```bash
# Copy API files
cp -r backend/api/* public/api/

# Move middleware
cp backend/middleware/auth.php includes/middleware.php

# Update paths (automated in script)
```

## ✅ Testing

After migration, test:
```bash
# Test API
curl http://your-domain/api/auth/check.php

# Test login page
curl http://your-domain/login.php
```

## 📍 URL Structure

- Login: `http://your-domain/login.php`
- Dashboard: `http://your-domain/dashboard.php`
- API: `http://your-domain/api/auth/login.php`
- CSS: `http://your-domain/css/main.css`
- JS: `http://your-domain/js/app.js`

All accessible directly! ✅

