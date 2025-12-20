# Migration Guide - Enterprise Version

## Upgrading to Enterprise Multi-School System

### Step 1: Backup Current Database

```bash
mysqldump -u root school_bus_tracking > backup_before_migration.sql
```

### Step 2: Review New Schema

The new schema (`schema-v2-enterprise.sql`) includes:
- Multi-school support
- Enhanced user management
- Attendance tracking
- Maintenance records
- Reports system
- Audit logs
- And more...

### Step 3: Migration Options

#### Option A: Fresh Installation (Recommended for New Schools)

```bash
# Create new database
mysql -u root -p <<EOF
CREATE DATABASE school_bus_tracking_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
EOF

# Import new schema
mysql -u root -p school_bus_tracking_v2 < database/schema-v2-enterprise.sql
```

#### Option B: Migrate Existing Data

1. Create new tables alongside existing ones
2. Migrate data gradually
3. Update application code
4. Test thoroughly
5. Switch over

### Step 4: Data Migration Script

```sql
-- Migrate existing users to new schema
INSERT INTO schools (school_name, school_code, email, status)
VALUES ('Default School', 'SCH001', 'admin@school.com', 'active');

SET @school_id = LAST_INSERT_ID();

-- Update users with school_id
UPDATE users SET school_id = @school_id WHERE school_id IS NULL;

-- Migrate buses
UPDATE buses SET school_id = @school_id WHERE school_id IS NULL;

-- Create default academic year
INSERT INTO academic_years (school_id, year_name, start_date, end_date, is_current)
VALUES (@school_id, '2024-2025', '2024-09-01', '2025-06-30', TRUE);
```

### Step 5: Update Configuration

Update `config/config.php` to include school context:

```php
// Add to session after login
$_SESSION['school_id'] = $user['school_id'];
```

### Step 6: Test

1. Test login with existing credentials
2. Verify data migration
3. Test new features
4. Check reports

### Important Notes

- Old API endpoints still work (backward compatible)
- New features require new schema
- Gradual migration recommended
- Keep backup for rollback

