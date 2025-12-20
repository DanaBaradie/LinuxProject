# Public Directory Structure

## ✅ Correct Structure

All web-accessible files go in `public/`, but backend code stays outside for security.

```
/
├── public/                    # Web Root (ALL web-accessible files)
│   ├── index.php            # Main entry point
│   ├── login.php            # Login page
│   ├── dashboard.php        # Dashboard
│   ├── api/                 # API endpoints (web accessible)
│   │   ├── auth/
│   │   ├── buses/
│   │   ├── routes/
│   │   ├── gps/
│   │   └── notifications/
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript
│   ├── assets/              # Images, icons, etc.
│   └── uploads/             # User uploads
│
├── config/                   # Config files (NOT web accessible, included only)
│   ├── config.php
│   └── database.php
│
├── app/                      # Application code (NOT web accessible)
│   └── Services/
│
├── includes/                 # PHP includes (NOT web accessible)
│   ├── header.php
│   ├── footer.php
│   └── sidebar.php
│
└── database/                 # SQL files (NOT web accessible)
    ├── schema.sql
    └── seed.sql
```

## 🔒 Security Note

- Files in `public/` = Directly accessible via URL
- Files outside `public/` = Only accessible via PHP includes (secure)

