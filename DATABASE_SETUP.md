# Database Setup Guide - Step by Step

## Understanding the Password Prompt

When you see `mysql -u root -p`, the `-p` means it will ask for a password.

---

## Option 1: If You Have a MySQL Password

### Step-by-Step:

1. **Enter the command:**
   ```bash
   mysql -u root -p
   ```

2. **When prompted "Enter password:", type your MySQL root password**
   - You won't see characters as you type (this is normal for security)
   - Press Enter after typing

3. **Once logged in, run:**
   ```sql
   CREATE DATABASE school_bus_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```

4. **Import schema (will ask for password again):**
   ```bash
   mysql -u root -p school_bus_tracking < database/schema.sql
   ```
   - Enter password when prompted

5. **Import seed data:**
   ```bash
   mysql -u root -p school_bus_tracking < database/seed.sql
   ```
   - Enter password when prompted

---

## Option 2: If You DON'T Have a Password (Empty Password)

### Method A: Remove `-p` flag

```bash
# Create database
mysql -u root
```

Then in MySQL:
```sql
CREATE DATABASE school_bus_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

```bash
# Import schema (no password needed)
mysql -u root school_bus_tracking < database/schema.sql

# Import seed data
mysql -u root school_bus_tracking < database/seed.sql
```

### Method B: Use `-p` but press Enter when asked

```bash
mysql -u root -p
# When asked for password, just press Enter (empty password)
```

---

## Option 3: Set a Password (Recommended for Security)

### If you want to set a password for root:

1. **Login to MySQL:**
   ```bash
   mysql -u root
   ```

2. **Set password:**
   ```sql
   ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_new_password';
   FLUSH PRIVILEGES;
   EXIT;
   ```

3. **Now use the password:**
   ```bash
   mysql -u root -p
   # Enter: your_new_password
   ```

4. **Update config/database.php:**
   ```php
   private $password = 'your_new_password';
   ```

---

## Option 4: Windows PowerShell/Command Prompt

### If you're on Windows:

**PowerShell:**
```powershell
# Create database
mysql -u root -p
# Enter password when prompted
```

**Or if no password:**
```powershell
mysql -u root
```

**Import files:**
```powershell
# Make sure you're in the project directory
mysql -u root -p school_bus_tracking < database\schema.sql
mysql -u root -p school_bus_tracking < database\seed.sql
```

---

## Option 5: Using phpMyAdmin (Easier GUI Method)

If you have phpMyAdmin installed:

1. **Open phpMyAdmin** in browser (usually `http://localhost/phpmyadmin`)

2. **Click "SQL" tab**

3. **Create database:**
   ```sql
   CREATE DATABASE school_bus_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Select the database** from left sidebar

5. **Click "Import" tab**

6. **Choose file:** `database/schema.sql`
   - Click "Go"

7. **Import again:** `database/seed.sql`
   - Click "Go"

**Done!** ✅

---

## Option 6: Using MySQL Workbench

1. **Open MySQL Workbench**
2. **Connect to your server**
3. **Click "File" → "Run SQL Script"**
4. **Select:** `database/schema.sql`
5. **Click "Run"**
6. **Repeat for:** `database/seed.sql`

---

## Quick Test After Setup

```bash
mysql -u root -p school_bus_tracking
```

```sql
SHOW TABLES;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM buses;
EXIT;
```

**Expected output:**
- Should show 8 tables
- Users count: 1 (or more if seed data imported)
- Buses count: 0 or more

---

## Troubleshooting

### "Access denied" error
- **Problem:** Wrong password
- **Solution:** Try empty password (just press Enter) or reset password

### "Command not found"
- **Problem:** MySQL not in PATH
- **Solution:** 
  - Windows: Use full path like `C:\xampp\mysql\bin\mysql.exe -u root`
  - Linux/Mac: Install MySQL or use `sudo mysql -u root`

### "Can't connect to MySQL server"
- **Problem:** MySQL service not running
- **Solution:**
  - Windows: Start MySQL service in Services
  - Linux: `sudo service mysql start`
  - Mac: `brew services start mysql`

### File path issues (Windows)
- **Problem:** Backslashes in paths
- **Solution:** Use forward slashes or double backslashes:
  ```powershell
  mysql -u root school_bus_tracking < database\schema.sql
  # OR
  mysql -u root school_bus_tracking < database/schema.sql
  ```

---

## Recommended Approach for Beginners

**Use phpMyAdmin** (if available):
- ✅ Visual interface
- ✅ No command line needed
- ✅ Easy to see what's happening
- ✅ Error messages are clear

**Steps:**
1. Open `http://localhost/phpmyadmin`
2. Create database: `school_bus_tracking`
3. Select database
4. Import `schema.sql`
5. Import `seed.sql`
6. Done!

---

## After Database Setup

Don't forget to update `config/database.php`:

```php
private $host = 'localhost';
private $db_name = 'school_bus_tracking';
private $username = 'root';
private $password = '';  // Your MySQL password (or empty if no password)
```

---

**Need more help?** Check the error message and let me know what it says!

