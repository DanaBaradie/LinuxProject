# System Architecture Documentation

**School Bus Tracking System**  
**Author:** Dana Baradie  
**Course:** IT404  
**Date:** 2024

## 1. Overview

The School Bus Tracking System is a web-based application designed to provide real-time GPS tracking of school buses, enabling administrators to monitor fleet operations and parents to track their children's buses in real-time.

## 2. System Architecture

### 2.1 Technology Stack

- **Backend:** PHP 8.x
- **Database:** MariaDB/MySQL
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **UI Framework:** Bootstrap 5.3
- **Maps API:** Google Maps JavaScript API (with mock GPS fallback)
- **Web Server:** Nginx/Apache
- **Version Control:** Git

### 2.2 Architecture Pattern

The system follows a **RESTful API architecture** with clear separation of concerns:

```
┌─────────────────┐
│   Frontend      │  (HTML/CSS/JS)
│   (User UI)     │
└────────┬────────┘
         │ HTTP/JSON
         │
┌────────▼────────┐
│   Backend API   │  (PHP REST Endpoints)
│   (Business     │
│    Logic)       │
└────────┬────────┘
         │ PDO
         │
┌────────▼────────┐
│   Database      │  (MariaDB/MySQL)
│   (Data Layer)  │
└─────────────────┘
```

### 2.3 Directory Structure

```
/
├── backend/
│   ├── api/
│   │   ├── auth/          # Authentication endpoints
│   │   ├── buses/         # Bus management endpoints
│   │   ├── routes/        # Route management endpoints
│   │   ├── gps/           # GPS tracking endpoints
│   │   └── notifications/ # Notification endpoints
│   ├── config/            # Configuration files
│   ├── middleware/        # Authentication/Authorization middleware
│   └── services/          # Business logic services
├── frontend/
│   ├── admin/             # Admin-specific pages
│   ├── parent/            # Parent portal pages
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── assets/            # Images, icons, etc.
├── database/
│   ├── schema.sql         # Database schema
│   └── seed.sql           # Seed data
├── docs/                  # Documentation
└── public/                # Public web root (legacy)
```

## 3. Database Design

### 3.1 Entity Relationship Diagram

```
users (1) ────< (0..1) buses
  │
  │ (1)
  │
  └───< (1..N) students
  │
  └───< (1..N) notifications

buses (1) ────< (1..N) gps_logs
  │
  │ (N)
  │
  └───< (N) bus_routes >─── (N) routes

routes (1) ────< (1..N) route_stops
  │
  │ (1)
  │
  └───< (0..N) students
```

### 3.2 Key Tables

1. **users** - Stores user accounts (admin, driver, parent)
2. **buses** - Bus fleet information
3. **routes** - Bus route definitions
4. **route_stops** - Individual stops on routes
5. **students** - Student information linked to parents
6. **gps_logs** - Historical GPS tracking data
7. **notifications** - System notifications for parents
8. **bus_routes** - Many-to-many relationship between buses and routes

### 3.3 Database Features

- **Normalized Design:** 3NF normalization
- **Foreign Keys:** Referential integrity enforced
- **Indexes:** Optimized for common queries
- **Cascading Deletes:** Automatic cleanup of related records

## 4. Authentication & Authorization

### 4.1 Authentication Method

- **Session-based authentication** using PHP sessions
- Password hashing with `password_hash()` (bcrypt)
- Session timeout: 1 hour of inactivity

### 4.2 Role-Based Access Control (RBAC)

Three user roles with different permissions:

1. **Admin**
   - Full system access
   - Manage buses, routes, users, students
   - View all tracking data
   - Send notifications

2. **Driver**
   - Update own bus location
   - View assigned bus information
   - Limited access to routes

3. **Parent**
   - Track children's buses
   - View notifications
   - View assigned routes and stops

### 4.3 Security Measures

- Input sanitization and validation
- Prepared statements (PDO) to prevent SQL injection
- XSS protection via `htmlspecialchars()`
- CSRF protection (session-based)
- Password strength requirements
- Secure session management

## 5. API Design

### 5.1 RESTful Principles

- **Resource-based URLs:** `/api/buses`, `/api/routes`
- **HTTP Methods:** GET, POST, PUT, DELETE
- **JSON Responses:** Consistent format
- **Status Codes:** Proper HTTP status codes

### 5.2 Response Format

```json
{
  "success": true|false,
  "message": "Optional message",
  "data": {
    // Response data
  }
}
```

### 5.3 Error Handling

- Consistent error responses
- Proper HTTP status codes (400, 401, 403, 404, 500)
- Error logging for debugging
- User-friendly error messages

## 6. GPS Tracking System

### 6.1 Location Updates

- Drivers update location via mobile app or web interface
- GPS coordinates stored in `buses` table (current location)
- Historical data logged in `gps_logs` table
- Real-time updates every 5-30 seconds

### 6.2 Google Maps Integration

- Google Maps JavaScript API for map display
- Interactive markers for each bus
- Real-time position updates
- Route visualization
- Info windows with bus details

### 6.3 Mock GPS Mode

- Fallback when Google Maps API key is not configured
- Simple HTML/CSS-based map visualization
- Functional for demonstration purposes
- Allows system testing without API key

## 7. Notification System

### 7.1 Automated Notifications

The system automatically generates notifications for:

1. **Bus Nearby:** When bus is within 0.5km of a student's stop
2. **Speed Warning:** When bus exceeds speed limit (60 km/h)
3. **Traffic Alert:** Manual notifications for delays
4. **Route Change:** When routes are modified
5. **General:** System-wide announcements

### 7.2 Notification Delivery

- Stored in database
- Displayed in parent portal
- Real-time updates via AJAX
- Unread count indicator

## 8. Frontend Architecture

### 8.1 Component Structure

- **Modular JavaScript:** Separate files for maps, app logic
- **Reusable Components:** Cards, tables, forms
- **Responsive Design:** Mobile-first approach
- **Progressive Enhancement:** Works without JavaScript

### 8.2 Real-Time Updates

- AJAX polling every 5 seconds
- WebSocket-ready architecture (future enhancement)
- Optimistic UI updates
- Error handling and retry logic

## 9. Performance Considerations

### 9.1 Database Optimization

- Indexed columns for frequent queries
- Efficient JOIN operations
- Query result caching (future)
- Connection pooling

### 9.2 Frontend Optimization

- Minified CSS/JS (production)
- Image optimization
- Lazy loading of maps
- Debounced API calls

## 10. Scalability

### 10.1 Current Limitations

- Single server deployment
- Session-based authentication
- Synchronous notification processing

### 10.2 Future Enhancements

- Microservices architecture
- Redis for session management
- Message queue for notifications
- CDN for static assets
- Load balancing
- Database replication

## 11. Security Considerations

### 11.1 Implemented

- Password hashing (bcrypt)
- SQL injection prevention (PDO)
- XSS protection
- Session security
- Input validation

### 11.2 Recommendations

- HTTPS enforcement
- Rate limiting
- API key rotation
- Regular security audits
- Dependency updates

## 12. Deployment

### 12.1 Requirements

- PHP 8.0+
- MariaDB/MySQL 10.3+
- Web server (Nginx/Apache)
- SSL certificate (recommended)

### 12.2 Configuration

1. Update database credentials in `config/database.php`
2. Set Google Maps API key in `config/config.php`
3. Import database schema: `database/schema.sql`
4. Import seed data: `database/seed.sql`
5. Configure web server document root
6. Set proper file permissions

## 13. Testing

### 13.1 Test Scenarios

- User authentication
- GPS location updates
- Notification generation
- Role-based access
- Map rendering
- API endpoints

### 13.2 Test Data

Seed data includes:
- Admin user (admin@school.com / admin123)
- Sample drivers, parents, students
- Sample buses and routes
- Test GPS coordinates

## 14. Maintenance

### 14.1 Regular Tasks

- Database backup
- Log file rotation
- Security updates
- Performance monitoring
- User feedback review

### 14.2 Monitoring

- Error logs
- API response times
- Database query performance
- User activity logs

## 15. Conclusion

The School Bus Tracking System is designed as a production-ready application with:
- Clean architecture
- Secure coding practices
- Scalable design
- Comprehensive documentation
- User-friendly interface

The system can be extended with additional features such as:
- Mobile applications
- SMS/Email notifications
- Advanced analytics
- Route optimization
- Parent-driver communication

---

**Document Version:** 1.0  
**Last Updated:** 2024

