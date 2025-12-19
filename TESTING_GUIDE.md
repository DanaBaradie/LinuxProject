# Testing Guide - School Bus Tracking System

**Author:** Dana Baradie  
**Course:** IT404

This guide will walk you through testing all features of the system.

---

## Prerequisites

Before testing, ensure you have:
- ✅ PHP 8.0+ installed
- ✅ MariaDB/MySQL installed and running
- ✅ Web server (Apache/Nginx) configured
- ✅ Database created

---

## Step 1: Database Setup

### 1.1 Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE school_bus_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 1.2 Import Schema

```bash
mysql -u root -p school_bus_tracking < database/schema.sql
```

### 1.3 Import Seed Data (Optional but Recommended)

```bash
mysql -u root -p school_bus_tracking < database/seed.sql
```

### 1.4 Verify Database

```bash
mysql -u root -p school_bus_tracking
```

```sql
SHOW TABLES;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM buses;
EXIT;
```

You should see 8 tables and data in users/buses tables.

---

## Step 2: Configuration

### 2.1 Update Database Credentials

Edit `config/database.php`:

```php
private $host = 'localhost';
private $db_name = 'school_bus_tracking';
private $username = 'root';  // Your MySQL username
private $password = '';      // Your MySQL password
```

### 2.2 Update Site URL (if needed)

Edit `config/config.php`:

```php
define('SITE_URL', 'http://localhost');  // Or your server IP
```

### 2.3 Test Database Connection

Create a test file `test-db.php` in the root:

```php
<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connection successful!<br>";
    
    // Test query
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Users table accessible: " . $result['count'] . " users found<br>";
    
    echo "<br>✅ Database setup complete!";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
```

Visit: `http://localhost/test-db.php`

---

## Step 3: Test Authentication

### 3.1 Test Login Page

1. Navigate to: `http://localhost/login.php` (or your configured path)
2. You should see the login form

### 3.2 Test Admin Login

**Credentials:**
- Email: `admin@school.com`
- Password: `admin123`

**Expected Result:**
- ✅ Redirects to dashboard
- ✅ Shows admin dashboard with statistics
- ✅ Sidebar shows admin menu items

### 3.3 Test Other User Roles

If you imported seed data, test:

**Driver:**
- Email: `driver1@school.com`
- Password: `admin123`

**Parent:**
- Email: `parent1@school.com`
- Password: `admin123`

---

## Step 4: Test API Endpoints

### 4.1 Test Authentication API

#### Login Test

```bash
curl -X POST http://localhost/backend/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@school.com","password":"admin123"}' \
  -c cookies.txt
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "admin@school.com",
      "full_name": "System Administrator",
      "role": "admin"
    }
  }
}
```

#### Check Session

```bash
curl http://localhost/backend/api/auth/check.php \
  -b cookies.txt
```

#### Logout

```bash
curl -X POST http://localhost/backend/api/auth/logout.php \
  -b cookies.txt
```

### 4.2 Test Buses API

#### Get All Buses

```bash
curl http://localhost/backend/api/buses/index.php \
  -b cookies.txt
```

**Expected:** List of all buses

#### Get Single Bus

```bash
curl "http://localhost/backend/api/buses/get.php?id=1" \
  -b cookies.txt
```

#### Create Bus (Admin only)

```bash
curl -X POST http://localhost/backend/api/buses/create.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "bus_number": "BUS-TEST-001",
    "license_plate": "TEST-123",
    "capacity": 50,
    "status": "active"
  }'
```

### 4.3 Test GPS API

#### Update Location (Driver)

First, login as driver:
```bash
curl -X POST http://localhost/backend/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"driver1@school.com","password":"admin123"}' \
  -c driver-cookies.txt
```

Then update location:
```bash
curl -X POST http://localhost/backend/api/gps/update.php \
  -H "Content-Type: application/json" \
  -b driver-cookies.txt \
  -d '{
    "bus_id": 1,
    "latitude": 33.8886,
    "longitude": 35.4955,
    "speed": 45.5,
    "heading": 90
  }'
```

#### Get Live Locations

```bash
curl http://localhost/backend/api/gps/live.php \
  -b cookies.txt
```

#### Get GPS History

```bash
curl "http://localhost/backend/api/gps/history.php?bus_id=1&limit=10" \
  -b cookies.txt
```

### 4.4 Test Notifications API

#### Get Notifications (Parent)

Login as parent:
```bash
curl -X POST http://localhost/backend/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"parent1@school.com","password":"admin123"}' \
  -c parent-cookies.txt
```

Get notifications:
```bash
curl http://localhost/backend/api/notifications/index.php \
  -b parent-cookies.txt
```

---

## Step 5: Test Frontend Features

### 5.1 Admin Dashboard

1. Login as admin
2. Navigate to Dashboard
3. **Verify:**
   - ✅ Statistics cards show numbers
   - ✅ Recent buses table displays
   - ✅ Sidebar navigation works

### 5.2 Bus Management (Admin)

1. Navigate to **Manage Buses**
2. **Test:**
   - ✅ View list of buses
   - ✅ Add new bus (if form exists)
   - ✅ Edit existing bus
   - ✅ View bus details

### 5.3 Live Tracking (Admin)

1. Navigate to **Live Tracking** or `frontend/admin/live-tracking.php`
2. **Verify:**
   - ✅ Map loads (Google Maps or mock mode)
   - ✅ Bus markers appear (if buses have GPS data)
   - ✅ Bus list updates
   - ✅ Auto-refresh works

### 5.4 Driver Features

1. Login as driver (`driver1@school.com`)
2. Navigate to **Update Location**
3. **Test Manual Entry:**
   - Enter coordinates: Lat: `33.8886`, Lng: `35.4955`
   - Click "Update My Location Now"
   - ✅ Success message appears
   - ✅ Location updates in database

4. **Test GPS Auto-Detection:**
   - Click "Get My Current Location"
   - Allow browser location access
   - ✅ Location updates automatically

### 5.5 Parent Features

1. Login as parent (`parent1@school.com`)
2. Navigate to **Track Bus** or `frontend/parent/track-bus.php`
3. **Verify:**
   - ✅ Map displays
   - ✅ Bus markers show (if driver updated location)
   - ✅ Bus information cards display

4. Navigate to **My Children**
5. **Verify:**
   - ✅ Children list displays
   - ✅ Assigned stops shown

---

## Step 6: Test Notification System

### 6.1 Trigger Automated Notifications

#### Test "Bus Nearby" Notification

1. Login as driver
2. Update bus location to be near a stop:
   - Get stop coordinates from database:
   ```sql
   SELECT latitude, longitude FROM route_stops WHERE id = 1;
   ```
3. Update bus location to those coordinates (within 0.5km)
4. Login as parent
5. Check notifications
6. **Expected:** "Bus nearby" notification appears

#### Test Speed Warning

1. Login as driver
2. Update location with speed > 60 km/h:
   ```json
   {
     "bus_id": 1,
     "latitude": 33.8886,
     "longitude": 35.4955,
     "speed": 65.0
   }
   ```
3. Login as parent
4. Check notifications
5. **Expected:** Speed warning notification

### 6.2 Manual Notification Creation

1. Login as admin
2. Use API to create notification:
```bash
curl -X POST http://localhost/backend/api/notifications/create.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "parent_id": 4,
    "bus_id": 1,
    "message": "Test notification",
    "type": "general"
  }'
```

3. Login as parent
4. **Verify:** Notification appears in list

---

## Step 7: Test Map Integration

### 7.1 With Google Maps API Key

1. Add API key to `config/config.php`:
   ```php
   define('GOOGLE_MAPS_API_KEY', 'your-actual-api-key');
   ```

2. Navigate to tracking page
3. **Verify:**
   - ✅ Google Maps loads
   - ✅ Bus markers appear
   - ✅ Clicking markers shows info window

### 7.2 Without API Key (Mock Mode)

1. Remove or set invalid API key
2. Navigate to tracking page
3. **Verify:**
   - ✅ Mock map displays
   - ✅ Message shows "Mock GPS Mode"
   - ✅ Bus markers still appear (simplified)

---

## Step 8: Test Security

### 8.1 Test Authentication

1. Try accessing protected page without login
2. **Expected:** Redirects to login page

### 8.2 Test Authorization

1. Login as parent
2. Try accessing admin-only page (e.g., `/buses.php`)
3. **Expected:** Access denied or redirect

### 8.3 Test SQL Injection Protection

Try this in login form:
```
Email: admin@school.com' OR '1'='1
Password: anything
```

**Expected:** Login fails (not vulnerable)

### 8.4 Test XSS Protection

Try entering in any form:
```html
<script>alert('XSS')</script>
```

**Expected:** Script is escaped, not executed

---

## Step 9: Test Database Operations

### 9.1 Verify Data Integrity

```sql
-- Check foreign key constraints
SELECT b.bus_number, u.full_name 
FROM buses b 
LEFT JOIN users u ON b.driver_id = u.id;

-- Check GPS logs
SELECT COUNT(*) FROM gps_logs;

-- Check notifications
SELECT COUNT(*) FROM notifications WHERE is_read = FALSE;
```

### 9.2 Test Cascade Deletes

```sql
-- This should fail if students exist
DELETE FROM users WHERE id = 1;
```

**Expected:** Error or cascade delete (depending on setup)

---

## Step 10: Performance Testing

### 10.1 Test API Response Times

```bash
time curl http://localhost/backend/api/buses/index.php -b cookies.txt
```

**Expected:** Response time < 1 second

### 10.2 Test Concurrent Requests

Use a tool like Apache Bench:
```bash
ab -n 100 -c 10 http://localhost/login.php
```

---

## Quick Test Checklist

### Basic Functionality
- [ ] Database connection works
- [ ] Admin can login
- [ ] Dashboard displays
- [ ] Buses list loads
- [ ] Routes list loads

### API Endpoints
- [ ] Login API works
- [ ] Get buses API works
- [ ] GPS update API works
- [ ] Live tracking API works
- [ ] Notifications API works

### Frontend Features
- [ ] Map displays (Google or mock)
- [ ] Bus markers appear
- [ ] Location updates work
- [ ] Notifications display
- [ ] Forms submit correctly

### Security
- [ ] Unauthorized access blocked
- [ ] Role-based access works
- [ ] SQL injection protected
- [ ] XSS protected

### Notifications
- [ ] Automated notifications trigger
- [ ] Manual notifications create
- [ ] Notifications mark as read
- [ ] Unread count updates

---

## Troubleshooting

### Database Connection Fails
- Check credentials in `config/database.php`
- Verify MySQL service is running
- Check database exists

### API Returns 404
- Check web server rewrite rules
- Verify file paths are correct
- Check `.htaccess` (Apache) or nginx config

### Map Doesn't Load
- Check Google Maps API key (if using)
- System will use mock mode if key missing
- Check browser console for errors

### Notifications Not Appearing
- Verify parent has children registered
- Check bus has GPS location
- Verify notification service is called
- Check database for notification records

### Location Not Updating
- Verify driver is assigned to bus
- Check GPS coordinates are valid
- Verify API endpoint is accessible
- Check database for updates

---

## Test Data Reference

### Default Users
- **Admin:** admin@school.com / admin123
- **Driver:** driver1@school.com / admin123
- **Parent:** parent1@school.com / admin123

### Sample Coordinates (Beirut, Lebanon)
- Downtown: 33.8886, 35.4955
- Achrafieh: 33.9010, 35.5300
- Zahle: 33.8547, 35.8623

### Sample Bus
- Bus Number: BUS-001
- Driver: John Smith
- Status: Active

---

## Next Steps After Testing

1. **Fix any issues** found during testing
2. **Document bugs** and resolutions
3. **Optimize** slow queries or endpoints
4. **Add more test data** if needed
5. **Prepare demo** for presentation

---

**Testing Guide Version:** 1.0  
**Last Updated:** 2024

