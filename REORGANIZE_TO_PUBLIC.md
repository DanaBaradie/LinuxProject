# Reorganization Plan - Move Everything to Public

## Current Structure Issues
- API endpoints are in `backend/api/` (not directly web accessible)
- Need to move them to `public/api/` for direct web access

## New Structure

```
/var/www/bus-tracking/
├── public/                    # Web Root - ALL accessible files
│   ├── index.php
│   ├── login.php
│   ├── dashboard.php
│   ├── api/                  # API endpoints (moved from backend/api/)
│   │   ├── auth/
│   │   ├── buses/
│   │   ├── routes/
│   │   ├── gps/
│   │   └── notifications/
│   ├── css/
│   ├── js/
│   └── assets/
│
├── config/                   # Config (included, not web accessible)
│   ├── config.php
│   └── database.php
│
├── includes/                 # Includes (not web accessible)
│   ├── header.php
│   ├── footer.php
│   └── sidebar.php
│
├── app/                      # Services (not web accessible)
│   └── Services/
│
└── database/                 # SQL files (not web accessible)
```

## Steps to Reorganize

1. Move `backend/api/*` → `public/api/`
2. Update paths in moved files (change `../../config/` to `../../config/`)
3. Keep config, includes, app outside public (security)
4. Update web server to point to `public/` as document root

