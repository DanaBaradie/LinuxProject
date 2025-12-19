# Remote Server Setup Guide

## Step 1: Connect to Your Server

### From Windows PowerShell:

```powershell
ssh root@165.22.21.116
```

**When prompted for password:** Type your server password (the one you were trying: `D@N@SerVeR!@#&05M`)

**Note:** In PowerShell, if password has special characters, you might need to escape them or type it carefully.

---

## Step 2: Navigate to Your Project

Once connected to the server:

```bash
# Find your project directory
cd /var/www/html
# OR
cd /home/root/LinuxProject
# OR wherever you uploaded the project

# List files to confirm
ls -la
```

---

## Step 3: Fix MySQL Access

### Option A: Use sudo (Recommended)

On Ubuntu, MySQL root might require sudo:

```bash
# Create database
sudo mysql -u root

# In MySQL prompt:
CREATE DATABASE school_bus_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Import schema
sudo mysql -u root school_bus_tracking < database/schema.sql

# Import seed data
sudo mysql -u root school_bus_tracking < database/seed.sql
```

### Option B: Reset MySQL Root Password

If you need to set/reset MySQL root password:

```bash
# Stop MySQL
sudo systemctl stop mysql

# Start MySQL in safe mode
sudo mysqld_safe --skip-grant-tables &

# Connect without password
mysql -u root

# In MySQL:
USE mysql;
ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_new_password';
FLUSH PRIVILEGES;
EXIT;

# Restart MySQL
sudo systemctl restart mysql

# Now use the new password
mysql -u root -p
```

### Option C: Create MySQL User for Application

Better security practice:

```bash
sudo mysql -u root

# In MySQL:
CREATE DATABASE school_bus_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'bus_tracking_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON school_bus_tracking.* TO 'bus_tracking_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import with new user
mysql -u bus_tracking_user -p school_bus_tracking < database/schema.sql
mysql -u bus_tracking_user -p school_bus_tracking < database/seed.sql
```

Then update `config/database.php`:
```php
private $username = 'bus_tracking_user';
private $password = 'secure_password_here';
```

---

## Step 4: Complete Database Setup

### Navigate to project directory first:

```bash
cd /path/to/your/project
pwd  # Verify you're in the right place
ls database/  # Should see schema.sql and seed.sql
```

### Then run:

```bash
# Method 1: Using sudo (if needed)
sudo mysql -u root < database/schema.sql
sudo mysql -u root school_bus_tracking < database/schema.sql
sudo mysql -u root school_bus_tracking < database/seed.sql

# Method 2: If you have password
mysql -u root -p school_bus_tracking < database/schema.sql
# Enter password when prompted

mysql -u root -p school_bus_tracking < database/seed.sql
# Enter password when prompted
```

---

## Step 5: Verify Database Setup

```bash
mysql -u root -p school_bus_tracking
# OR
sudo mysql -u root school_bus_tracking
```

```sql
SHOW TABLES;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM buses;
EXIT;
```

**Expected:** Should show 8 tables and some data.

---

## Step 6: Update Configuration

Edit `config/database.php` on the server:

```bash
nano config/database.php
# OR
vi config/database.php
```

Update:
```php
private $host = 'localhost';
private $db_name = 'school_bus_tracking';
private $username = 'root';  // or 'bus_tracking_user'
private $password = '';       // Your MySQL password
```

Save and exit (Ctrl+X, then Y, then Enter for nano)

---

## Step 7: Set File Permissions

```bash
# Make sure web server can read files
chmod 755 -R .
chmod 644 config/*.php
chmod 644 database/*.sql

# If you have a storage directory
chmod 777 -R storage/  # If exists
```

---

## Step 8: Test Connection

Create a test file:

```bash
nano test-db.php
```

Paste:
```php
<?php
require_once 'config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connected!<br>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users: " . $result['count'];
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
```

Visit: `http://165.22.21.116/test-db.php`

---

## Troubleshooting

### "Access denied" Error

**Try sudo:**
```bash
sudo mysql -u root
```

### "Command not found: mysql"

**Install MySQL client:**
```bash
sudo apt update
sudo apt install mysql-client
```

### "Can't connect to MySQL server"

**Check if MySQL is running:**
```bash
sudo systemctl status mysql
```

**Start MySQL:**
```bash
sudo systemctl start mysql
```

### SSH Connection Timeout

**Check firewall:**
```bash
sudo ufw status
sudo ufw allow 22/tcp  # If SSH not allowed
```

**Check SSH service:**
```bash
sudo systemctl status ssh
sudo systemctl start ssh
```

### File Not Found (schema.sql)

**Find your project:**
```bash
find / -name "schema.sql" 2>/dev/null
```

**Or check common locations:**
```bash
ls /var/www/html/
ls /home/root/
ls /root/
```

---

## Quick Setup Script

Save this as `setup-db.sh`:

```bash
#!/bin/bash

# Create database
sudo mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS school_bus_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF

# Import schema
sudo mysql -u root school_bus_tracking < database/schema.sql

# Import seed data
sudo mysql -u root school_bus_tracking < database/seed.sql

echo "✅ Database setup complete!"
```

Make executable and run:
```bash
chmod +x setup-db.sh
./setup-db.sh
```

---

## Complete Step-by-Step for Your Server

```bash
# 1. Connect
ssh root@165.22.21.116

# 2. Navigate to project
cd /var/www/html  # or wherever your project is
ls -la database/  # verify files exist

# 3. Create database (using sudo)
sudo mysql -u root <<EOF
CREATE DATABASE school_bus_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
EOF

# 4. Import schema
sudo mysql -u root school_bus_tracking < database/schema.sql

# 5. Import seed data
sudo mysql -u root school_bus_tracking < database/seed.sql

# 6. Verify
sudo mysql -u root school_bus_tracking -e "SHOW TABLES;"

# 7. Update config
nano config/database.php
# Set password if you created one

# 8. Test
php test-db.php
```

---

**Need help?** Share the output of these commands:
```bash
pwd
ls -la database/
sudo mysql -u root -e "SHOW DATABASES;"
```

