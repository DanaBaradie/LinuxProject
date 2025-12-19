# User Manual

**School Bus Tracking System**  
**Author:** Dana Baradie  
**Course:** IT404

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Admin Guide](#admin-guide)
4. [Driver Guide](#driver-guide)
5. [Parent Guide](#parent-guide)
6. [Troubleshooting](#troubleshooting)
7. [FAQ](#faq)

---

## Introduction

The School Bus Tracking System allows you to:
- Track school buses in real-time
- Receive notifications about bus status
- Manage routes and schedules
- Monitor fleet operations

---

## Getting Started

### Login

1. Navigate to the login page
2. Enter your email and password
3. Click "Sign In"

**Default Admin Credentials:**
- Email: `admin@school.com`
- Password: `admin123`

### Dashboard

After logging in, you'll see your role-specific dashboard with:
- Quick statistics
- Recent activity
- Navigation menu

---

## Admin Guide

### Managing Buses

#### Add New Bus

1. Navigate to **Manage Buses**
2. Click **Add New Bus**
3. Fill in the form:
   - Bus Number (required, unique)
   - License Plate
   - Capacity (number of students)
   - Driver (select from dropdown)
   - Status (Active/Inactive/Maintenance)
4. Click **Save**

#### Edit Bus

1. Go to **Manage Buses**
2. Click **Edit** on the bus you want to modify
3. Update the information
4. Click **Save**

#### Assign Driver

1. Edit the bus
2. Select a driver from the dropdown
3. Save changes

### Managing Routes

#### Create Route

1. Navigate to **Manage Routes**
2. Click **Add New Route**
3. Enter route details:
   - Route Name
   - Description
   - Start Time
   - End Time
4. Click **Save**

#### Add Stops to Route

1. Edit the route
2. Click **Add Stop**
3. Enter stop details:
   - Stop Name
   - Latitude
   - Longitude
   - Stop Order
   - Estimated Arrival Time
4. Click **Save**

#### Assign Bus to Route

1. Edit the route
2. Select a bus from the dropdown
3. Save changes

### Managing Users

#### Add User

1. Navigate to **Manage Users**
2. Click **Add New User**
3. Fill in:
   - Email (required, unique)
   - Full Name
   - Phone
   - Role (Admin/Driver/Parent)
   - Password (minimum 8 characters)
4. Click **Save**

#### Edit User

1. Go to **Manage Users**
2. Click **Edit** on the user
3. Update information
4. Save changes

### Managing Students

#### Add Student

1. Navigate to **Manage Students**
2. Click **Add New Student**
3. Enter:
   - Student Name
   - Parent (select from dropdown)
   - Grade
   - Assigned Stop
4. Click **Save**

### Live Tracking

1. Navigate to **Live Tracking**
2. View all active buses on the map
3. Click on a bus marker to see details
4. Map auto-refreshes every 5 seconds

### Sending Notifications

1. Navigate to **Notifications** (or use API)
2. Select parent(s)
3. Choose notification type
4. Enter message
5. Send

---

## Driver Guide

### Viewing Your Bus

1. Login with driver credentials
2. Dashboard shows your assigned bus
3. Navigate to **My Bus** for details

### Updating Location

#### Automatic GPS (Recommended)

1. Navigate to **Update Location**
2. Click **Get My Current Location**
3. Allow browser location access
4. Location updates automatically

**Note:** Requires HTTPS connection

#### Manual Entry

1. Go to **Update Location**
2. Enter coordinates:
   - Latitude (e.g., 33.8886)
   - Longitude (e.g., 35.4955)
3. Click **Update My Location Now**

**Quick Locations:**
- Use preset buttons for common locations
- Or get coordinates from Google Maps

### Location Update Frequency

- Update every 30 seconds during active routes
- Update when arriving at stops
- Update when route changes

---

## Parent Guide

### Tracking Your Child's Bus

1. Login with parent credentials
2. Navigate to **Track Bus**
3. View real-time bus location on map
4. See estimated arrival time
5. Map auto-updates every 5 seconds

### Viewing Notifications

1. Go to **Notifications** (or check dashboard)
2. View all notifications
3. Unread notifications are highlighted
4. Click notification to mark as read
5. Click **Mark All as Read** to clear all

### Notification Types

- **Nearby:** Bus is approaching your stop
- **Speed Warning:** Bus speed alert
- **Traffic:** Traffic delays
- **Route Change:** Route modifications
- **General:** System announcements

### Viewing Your Children

1. Navigate to **My Children**
2. See list of registered children
3. View assigned stops
4. View bus information

---

## Troubleshooting

### Login Issues

**Problem:** Cannot login  
**Solution:**
- Check email and password
- Clear browser cache
- Check if account is active
- Contact administrator

### Location Not Updating

**Problem:** Bus location not showing  
**Solution:**
- Check GPS coordinates are valid
- Verify driver has updated location
- Check bus status is "Active"
- Refresh the page

### Map Not Loading

**Problem:** Map shows error or blank  
**Solution:**
- Check internet connection
- Verify Google Maps API key (if configured)
- System will use mock GPS mode if API key missing
- Try different browser

### Notifications Not Received

**Problem:** Not receiving notifications  
**Solution:**
- Check notification settings
- Verify you have children registered
- Check assigned stops are correct
- Refresh notifications page

### Slow Performance

**Problem:** System is slow  
**Solution:**
- Check internet connection
- Clear browser cache
- Close unnecessary tabs
- Try different browser

---

## FAQ

### Q: How often is the bus location updated?

**A:** Location updates every 5-30 seconds when driver is actively updating. Historical data is stored for analysis.

### Q: Can I track multiple buses?

**A:** Parents can track buses assigned to their children's routes. Admins can track all buses.

### Q: What if I forget my password?

**A:** Contact the system administrator to reset your password.

### Q: Is my location data secure?

**A:** Yes, all data is encrypted in transit and stored securely. Only authorized users can access location data.

### Q: Can I use this on mobile?

**A:** Yes, the system is responsive and works on mobile devices. For best experience, use the mobile browser or install as PWA.

### Q: How do I get GPS coordinates?

**A:** 
1. Open Google Maps
2. Right-click on location
3. Click coordinates to copy
4. Or use browser geolocation API

### Q: What browsers are supported?

**A:** 
- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers

### Q: Can I export tracking data?

**A:** Currently not available in UI. Contact administrator for data export. API endpoints available for developers.

### Q: How accurate is the GPS tracking?

**A:** Accuracy depends on device GPS. Typically within 5-10 meters. System uses best available location data.

### Q: What happens if the bus GPS fails?

**A:** System will show last known location. Driver can manually update location. Administrator will be notified.

---

## Support

For technical support or questions:
- Email: support@school.com
- Phone: (961) 1-234-5678
- Office Hours: Monday-Friday, 8 AM - 5 PM

---

## Keyboard Shortcuts

- `Ctrl + /` - Search
- `Esc` - Close modals
- `F5` - Refresh page
- `Ctrl + K` - Quick actions (future)

---

## Best Practices

### For Drivers

- Update location regularly
- Update when arriving at stops
- Report issues immediately
- Keep device charged

### For Parents

- Check notifications regularly
- Arrive at stop 5 minutes early
- Report concerns to administrator
- Keep contact information updated

### For Administrators

- Regular system maintenance
- Monitor bus status
- Review notifications
- Update routes as needed
- Backup data regularly

---

**Document Version:** 1.0  
**Last Updated:** 2024

