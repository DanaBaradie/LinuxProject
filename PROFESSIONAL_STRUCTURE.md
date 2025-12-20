# Professional Enterprise Structure

## New Directory Structure

```
/
├── app/
│   ├── Controllers/          # MVC Controllers (future)
│   ├── Models/               # Data Models
│   ├── Services/             # Business Logic
│   │   ├── AttendanceService.php
│   │   ├── NotificationService.php
│   │   ├── ReportService.php
│   │   ├── SMSService.php
│   │   └── EmailService.php
│   ├── Middleware/           # Request Middleware
│   │   ├── AuthMiddleware.php
│   │   ├── SchoolMiddleware.php
│   │   └── PermissionMiddleware.php
│   └── Helpers/              # Utility Functions
│       ├── Validator.php
│       ├── Formatter.php
│       └── Logger.php
│
├── api/v1/                   # Versioned API
│   ├── auth/
│   ├── schools/
│   ├── buses/
│   ├── routes/
│   ├── students/
│   ├── attendance/
│   ├── notifications/
│   ├── reports/
│   ├── maintenance/
│   └── settings/
│
├── config/
│   ├── database.php
│   ├── app.php
│   ├── mail.php
│   ├── sms.php
│   └── integrations.php
│
├── database/
│   ├── migrations/           # Database migrations
│   ├── schema-v2-enterprise.sql
│   └── seeds/
│
├── public/                   # Web root
│   ├── index.php
│   ├── assets/
│   └── uploads/
│
├── resources/
│   ├── views/               # Frontend templates
│   │   ├── admin/
│   │   ├── parent/
│   │   └── driver/
│   ├── lang/                # Translations
│   └── email/               # Email templates
│
├── storage/
│   ├── logs/
│   ├── cache/
│   └── reports/
│
└── tests/                   # Unit tests
```

