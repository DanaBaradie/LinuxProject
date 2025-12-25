# School Bus Tracking System

**Author:** Dana Baradie  
**Course:** IT404  
**Project:** Complete Production-Quality School Bus Tracking System

## Overview

A comprehensive web-based School Bus Tracking System that enables real-time GPS tracking of school buses, providing administrators with fleet management capabilities and parents with live bus location updates and automated notifications.

## Features

### Core Functionality
- ✅ Real-time GPS tracking of school buses
- ✅ Role-based access control (Admin, Driver, Parent)
- ✅ Automated notification system (bus nearby, speed warnings, traffic alerts)
- ✅ Historical GPS data logging
- ✅ Route and stop management
- ✅ Student registration and assignment
- ✅ Google Maps integration with mock GPS fallback
- ✅ Responsive web interface

### Security Features
- ✅ Secure password hashing (bcrypt)
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS protection
- ✅ Session-based authentication
- ✅ Role-based authorization
- ✅ Input validation and sanitization

### Technical Features
- ✅ RESTful API architecture
- ✅ Clean code structure
- ✅ Comprehensive documentation
- ✅ Database normalization
- ✅ Error handling and logging
- ✅ Responsive design (Bootstrap 5)

## Technology Stack

- **Backend:** PHP 8.x
- **Database:** MariaDB/MySQL
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **UI Framework:** Bootstrap 5.3
- **Maps:** Google Maps JavaScript API (with mock GPS fallback)
- **Web Server:** Nginx/Apache

## Project Structure

```
/
├── backend/
│   ├── api/
│   │   ├── auth/              # Authentication endpoints
│   │   ├── buses/             # Bus management endpoints
│   │   ├── routes/            # Route management endpoints
│   │   ├── gps/               # GPS tracking endpoints
│   │   └── notifications/     # Notification endpoints
│   ├── config/                # Configuration files
│   ├── middleware/            # Auth middleware
│   └── services/              # Business logic services
├── frontend/
│   ├── admin/                 # Admin-specific pages
│   ├── parent/                # Parent portal pages
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript files
│   └── assets/                # Images, icons, etc.
├── database/
│   ├── schema.sql             # Database schema
│   └── seed.sql               # Seed data
├── docs/                      # Documentation
│   ├── system-architecture.md
│   ├── api-documentation.md
│   └── user-manual.md
└── public/                    # Public web root (legacy support)
```

## Installation

### Prerequisites

- PHP 8.0 or higher
- MariaDB/MySQL 10.3 or higher
- Web server (Nginx or Apache)
- Composer (optional, for future dependencies)

### Step 1: Clone Repository

```bash
git clone <repository-url>
cd LinuxProject
```

### Step 2: Database Setup

1. Create database:
```sql
CREATE DATABASE school_bus_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import schema:
```bash
mysql -u root -p school_bus_tracking < database/schema.sql
```

3. Import seed data (optional):
```bash
mysql -u root -p school_bus_tracking < database/seed.sql
```

### Step 3: Configuration

1. Update database credentials in `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'school_bus_tracking';
private $username = 'your_username';
private $password = 'your_password';
```

2. Update site URL in `config/config.php`:
```php
define('SITE_URL', 'http://your-domain.com');
```

3. (Optional) Add Google Maps API key in `config/config.php`:
```php
define('GOOGLE_MAPS_API_KEY', 'your-api-key-here');
```
Get API key from: https://console.cloud.google.com/

### Step 4: Web Server Configuration

#### Apache (.htaccess)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Step 5: File Permissions

```bash
chmod 755 -R .
chmod 777 -R storage/  # If using file storage
```

### Step 6: Access Application

1. Navigate to: `http://your-domain.com/login.php`
2. Login with default admin credentials:
   - Email: `admin@school.com`
   - Password: `admin123`

## Default Credentials

### Admin
- Email: `admin@school.com`
- Password: `admin123`

### Sample Users (from seed data)
- Driver: `driver1@school.com` / `admin123`
- Parent: `parent1@school.com` / `admin123`

**⚠️ Important:** Change default passwords in production!

## API Documentation

Complete API documentation available in `docs/api-documentation.md`

### Quick API Examples

**Login:**
```bash
curl -X POST http://your-domain.com/backend/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@school.com","password":"admin123"}'
```

**Get Buses:**
```bash
curl http://your-domain.com/backend/api/buses \
  -H "Cookie: PHPSESSID=your-session-id"
```

**Update GPS Location:**
```bash
curl -X POST http://your-domain.com/backend/api/gps/update.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your-session-id" \
  -d '{"bus_id":1,"latitude":33.8886,"longitude":35.4955,"speed":45.5}'
```

## Usage

### For Administrators

1. **Manage Buses:** Add, edit, and assign drivers to buses
2. **Manage Routes:** Create routes and add stops
3. **Manage Users:** Add drivers and parents
4. **Live Tracking:** Monitor all buses in real-time
5. **Send Notifications:** Create system-wide announcements

### For Drivers

1. **Update Location:** Use GPS or manual entry to update bus location
2. **View Bus Info:** See assigned bus details
3. **Track Route:** View assigned route and stops

### For Parents

1. **Track Bus:** View real-time location of child's bus
2. **View Notifications:** Receive alerts about bus status
3. **View Children:** See registered children and assigned stops

## Documentation

- **System Architecture:** `docs/system-architecture.md`
- **API Documentation:** `docs/api-documentation.md`
- **User Manual:** `docs/user-manual.md`

## Features in Detail

### Real-Time Tracking

- GPS coordinates updated every 5-30 seconds
- Historical data stored for analysis
- Visual map display with Google Maps
- Mock GPS mode when API key not configured

### Automated Notifications

- **Bus Nearby:** Sent when bus is within 0.5km of stop
- **Speed Warning:** Sent when bus exceeds speed limit (60 km/h)
- **Traffic Alert:** Manual notifications for delays
- **Route Change:** Automatic when routes modified
- **General:** System-wide announcements

### Security

- Password hashing with bcrypt
- Prepared statements prevent SQL injection
- XSS protection via htmlspecialchars
- Session-based authentication
- Role-based access control
- Input validation and sanitization

## Development

### Code Structure

- **Backend:** RESTful API with clean separation of concerns
- **Frontend:** Modular JavaScript with reusable components
- **Database:** Normalized schema with proper indexes
- **Documentation:** Comprehensive inline and external docs

### Adding New Features

1. Create API endpoint in `backend/api/`
2. Add middleware if authentication required
3. Update frontend JavaScript if needed
4. Update documentation
5. Test thoroughly

## Testing

### Test Scenarios

1. User authentication and authorization
2. GPS location updates
3. Notification generation
4. Map rendering (with and without API key)
5. Role-based access restrictions

### Test Data

Seed data includes:
- Admin user
- Sample drivers, parents, students
- Sample buses and routes
- Test GPS coordinates

## Troubleshooting

### Common Issues

**Database Connection Error:**
- Check credentials in `config/database.php`
- Verify database exists and is accessible
- Check MySQL service is running

**Map Not Loading:**
- Verify Google Maps API key (if using)
- System will use mock GPS mode if key missing
- Check browser console for errors

**Session Issues:**
- Clear browser cookies
- Check PHP session configuration
- Verify session storage permissions

**Location Not Updating:**
- Verify driver has updated location
- Check bus status is "Active"
- Verify GPS coordinates are valid

## Performance

### Optimization Tips

- Enable PHP OPcache
- Use database indexes (already implemented)
- Minify CSS/JS in production
- Enable gzip compression
- Use CDN for static assets
- Implement caching (future enhancement)

## Security Recommendations

1. **Change default passwords** immediately
2. **Use HTTPS** in production
3. **Regular security updates** for PHP and dependencies
4. **Implement rate limiting** for API endpoints
5. **Regular backups** of database
6. **Monitor error logs** for suspicious activity

## Future Enhancements

- Mobile applications (iOS/Android)
- SMS/Email notifications
- Advanced analytics and reporting
- Route optimization algorithms
- Parent-driver messaging
- WebSocket for real-time updates
- Multi-language support
- Dark mode theme

## License

This project is developed for academic purposes (IT404 course).

## Support

For issues, questions, or contributions:
- Review documentation in `docs/` folder
- Check API documentation for endpoint details
- Contact system administrator

## Credits

**Developer:** Dana Baradie  
**Course:** IT404  
**Institution:** [Your Institution]

---

## Quick Start Checklist

- [ ] Install PHP 8.0+
- [ ] Install MariaDB/MySQL
- [ ] Create database
- [ ] Import schema.sql
- [ ] Import seed.sql (optional)
- [ ] Configure database.php
- [ ] Configure config.php
- [ ] Set up web server
- [ ] Test login with admin credentials
- [ ] (Optional) Add Google Maps API key
- [ ] Review documentation

---

**Version:** 1.0  
**Last Updated:** 2024
