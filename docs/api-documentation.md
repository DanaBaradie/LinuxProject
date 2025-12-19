# API Documentation

**School Bus Tracking System**  
**Author:** Dana Baradie  
**Course:** IT404

## Base URL

All API endpoints are prefixed with `/backend/api`

## Authentication

Most endpoints require authentication via session. Include session cookie in requests.

## Response Format

All responses are in JSON format:

```json
{
  "success": true|false,
  "message": "Optional message",
  "data": {
    // Response data
  }
}
```

## Error Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed
- `409` - Conflict
- `500` - Internal Server Error

---

## Authentication Endpoints

### POST /auth/login

Login user and create session.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "full_name": "John Doe",
      "role": "admin"
    },
    "session_id": "abc123..."
  },
  "message": "Login successful"
}
```

---

### POST /auth/logout

Logout user and destroy session.

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### GET /auth/check

Check if session is valid.

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "full_name": "John Doe",
      "role": "admin"
    },
    "session_id": "abc123..."
  },
  "message": "Session valid"
}
```

---

## Bus Endpoints

### GET /buses

Get list of buses.

**Query Parameters:**
- None

**Response:**
```json
{
  "success": true,
  "data": {
    "buses": [
      {
        "id": 1,
        "bus_number": "BUS-001",
        "license_plate": "ABC-123",
        "capacity": 50,
        "current_latitude": "33.8886",
        "current_longitude": "35.4955",
        "last_location_update": "2024-01-15 10:30:00",
        "status": "active",
        "driver_id": 2,
        "driver_name": "John Smith"
      }
    ]
  }
}
```

**Access:** Admin (all buses), Driver (own bus), Parent (children's buses)

---

### GET /buses/get.php?id=1

Get single bus by ID.

**Query Parameters:**
- `id` (required) - Bus ID

**Response:**
```json
{
  "success": true,
  "data": {
    "bus": {
      "id": 1,
      "bus_number": "BUS-001",
      // ... bus details
    }
  }
}
```

---

### POST /buses/create.php

Create new bus. (Admin only)

**Request Body:**
```json
{
  "bus_number": "BUS-004",
  "license_plate": "XYZ-789",
  "capacity": 55,
  "driver_id": 3,
  "status": "active"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "bus": {
      "id": 4,
      // ... bus details
    }
  },
  "message": "Bus created successfully"
}
```

---

### PUT /buses/update.php

Update existing bus. (Admin only)

**Request Body:**
```json
{
  "id": 1,
  "capacity": 60,
  "driver_id": 2,
  "status": "active"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "bus": {
      // ... updated bus details
    }
  },
  "message": "Bus updated successfully"
}
```

---

### DELETE /buses/delete.php?id=1

Delete bus. (Admin only)

**Query Parameters:**
- `id` (required) - Bus ID

**Response:**
```json
{
  "success": true,
  "message": "Bus deleted successfully"
}
```

---

## GPS Endpoints

### POST /gps/update.php

Update bus GPS location.

**Request Body:**
```json
{
  "bus_id": 1,
  "latitude": 33.8886,
  "longitude": 35.4955,
  "speed": 45.5,
  "heading": 90
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "bus": {
      // ... bus details
    },
    "location": {
      "latitude": 33.8886,
      "longitude": 35.4955,
      "speed": 45.5,
      "heading": 90,
      "timestamp": "2024-01-15 10:30:00"
    }
  },
  "message": "Location updated successfully"
}
```

**Access:** Driver (own bus), Admin (any bus)

**Note:** Automatically triggers notification checks for nearby stops and speed warnings.

---

### GET /gps/live.php

Get live bus locations.

**Query Parameters:**
- `bus_id` (optional) - Filter by specific bus

**Response:**
```json
{
  "success": true,
  "data": {
    "buses": [
      {
        "id": 1,
        "bus_number": "BUS-001",
        "location": {
          "latitude": 33.8886,
          "longitude": 35.4955
        },
        "speed": 45.5,
        "heading": 90,
        "last_update": "Jan 15, 2024 10:30 AM",
        "status": "active"
      }
    ]
  }
}
```

---

### GET /gps/history.php

Get GPS history for a bus.

**Query Parameters:**
- `bus_id` (required) - Bus ID
- `limit` (optional) - Number of records (default: 100, max: 1000)

**Response:**
```json
{
  "success": true,
  "data": {
    "bus_id": 1,
    "logs": [
      {
        "id": 1,
        "latitude": 33.8886,
        "longitude": 35.4955,
        "speed": 45.5,
        "heading": 90,
        "timestamp": "2024-01-15 10:30:00",
        "timestamp_formatted": "Jan 15, 2024 10:30 AM"
      }
    ],
    "count": 50
  }
}
```

---

## Route Endpoints

### GET /routes

Get list of routes.

**Query Parameters:**
- `active_only` (optional) - Filter active routes only (true/false)

**Response:**
```json
{
  "success": true,
  "data": {
    "routes": [
      {
        "id": 1,
        "route_name": "Route A - Downtown",
        "description": "Main route covering downtown Beirut",
        "start_time": "07:00:00",
        "end_time": "08:30:00",
        "active": true,
        "stop_count": 5,
        "bus_count": 1
      }
    ]
  }
}
```

---

## Notification Endpoints

### GET /notifications

Get notifications for current user (Parent only).

**Query Parameters:**
- `unread_only` (optional) - Filter unread notifications (true/false)

**Response:**
```json
{
  "success": true,
  "data": {
    "notifications": [
      {
        "id": 1,
        "bus_id": 1,
        "bus_number": "BUS-001",
        "message": "Bus is approaching your stop",
        "notification_type": "nearby",
        "is_read": false,
        "created_at": "2024-01-15 10:30:00",
        "created_at_formatted": "Jan 15, 2024 10:30 AM"
      }
    ],
    "unread_count": 3,
    "total": 10
  }
}
```

---

### PUT /notifications/mark-read.php

Mark notification(s) as read.

**Request Body:**
```json
{
  "notification_id": 1
}
```

OR

```json
{
  "mark_all": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

---

### POST /notifications/create.php

Create notification (Admin/Driver only).

**Request Body:**
```json
{
  "parent_id": 1,
  "bus_id": 1,
  "message": "Bus is delayed due to traffic",
  "type": "traffic"
}
```

**Notification Types:**
- `traffic` - Traffic delays
- `speed_warning` - Speed violations
- `nearby` - Bus approaching stop
- `route_change` - Route modifications
- `general` - General announcements

**Response:**
```json
{
  "success": true,
  "data": {
    "notification": {
      "id": 1,
      // ... notification details
    }
  },
  "message": "Notification created successfully"
}
```

---

## Example Usage

### JavaScript Fetch Example

```javascript
// Login
const response = await fetch('/backend/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    email: 'admin@school.com',
    password: 'admin123'
  })
});

const data = await response.json();
console.log(data);

// Get buses
const busesResponse = await fetch('/backend/api/buses');
const busesData = await busesResponse.json();
console.log(busesData);

// Update GPS location
const gpsResponse = await fetch('/backend/api/gps/update.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    bus_id: 1,
    latitude: 33.8886,
    longitude: 35.4955,
    speed: 45.5
  })
});
const gpsData = await gpsResponse.json();
console.log(gpsData);
```

---

## Rate Limiting

Currently no rate limiting implemented. Recommended for production:
- 100 requests per minute per user
- 1000 requests per hour per IP

---

## Versioning

Current API version: **v1**

Future versions will be prefixed: `/backend/api/v2/`

---

**Document Version:** 1.0  
**Last Updated:** 2024

