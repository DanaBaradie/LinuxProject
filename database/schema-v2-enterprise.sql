-- Enterprise School Bus Tracking System - Enhanced Schema
-- Multi-School Support | Professional Structure
-- Author: Dana Baradie | Course: IT404

-- ============================================
-- CORE ORGANIZATION TABLES
-- ============================================

-- Schools/Organizations (Multi-tenant support)
CREATE TABLE schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    school_code VARCHAR(50) UNIQUE NOT NULL,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Lebanon',
    postal_code VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    logo_url VARCHAR(500),
    timezone VARCHAR(50) DEFAULT 'Asia/Beirut',
    language VARCHAR(10) DEFAULT 'en',
    currency VARCHAR(10) DEFAULT 'USD',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    subscription_plan ENUM('free', 'basic', 'premium', 'enterprise') DEFAULT 'basic',
    subscription_expires_at DATE,
    max_buses INT DEFAULT 10,
    max_students INT DEFAULT 100,
    max_drivers INT DEFAULT 5,
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_school_code (school_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academic Years
CREATE TABLE academic_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    year_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    INDEX idx_school (school_id),
    INDEX idx_current (is_current)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Semesters/Terms
CREATE TABLE semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT NOT NULL,
    semester_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    INDEX idx_academic_year (academic_year_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ENHANCED USER MANAGEMENT
-- ============================================

-- Users (Enhanced with school support)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    alternate_phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(500),
    role ENUM('super_admin', 'admin', 'driver', 'parent', 'staff', 'monitor') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    password_reset_token VARCHAR(255),
    password_reset_expires TIMESTAMP NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255),
    preferences JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_school_email (school_id, email),
    INDEX idx_email (email),
    INDEX idx_school (school_id),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Permissions (Role-based access control)
CREATE TABLE user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_key VARCHAR(100) NOT NULL,
    granted BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_permission (user_id, permission_key),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ENHANCED BUS MANAGEMENT
-- ============================================

-- Buses (Enhanced)
CREATE TABLE buses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    bus_number VARCHAR(50) NOT NULL,
    license_plate VARCHAR(50),
    make VARCHAR(100),
    model VARCHAR(100),
    year YEAR,
    color VARCHAR(50),
    capacity INT NOT NULL,
    current_capacity INT DEFAULT 0,
    driver_id INT,
    monitor_id INT,
    current_latitude DECIMAL(10, 8),
    current_longitude DECIMAL(11, 8),
    last_location_update TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'maintenance', 'retired') DEFAULT 'active',
    insurance_number VARCHAR(100),
    insurance_expires DATE,
    registration_number VARCHAR(100),
    registration_expires DATE,
    fuel_type ENUM('diesel', 'petrol', 'electric', 'hybrid') DEFAULT 'diesel',
    gps_device_id VARCHAR(100),
    gps_device_type VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (monitor_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_school_bus (school_id, bus_number),
    INDEX idx_school (school_id),
    INDEX idx_bus_number (bus_number),
    INDEX idx_driver (driver_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bus Maintenance Records
CREATE TABLE bus_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    maintenance_type ENUM('routine', 'repair', 'inspection', 'emergency') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    cost DECIMAL(10, 2),
    service_provider VARCHAR(255),
    maintenance_date DATE NOT NULL,
    next_maintenance_date DATE,
    mileage_at_service INT,
    performed_by VARCHAR(255),
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    attachments JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_bus (bus_id),
    INDEX idx_maintenance_date (maintenance_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ENHANCED ROUTE MANAGEMENT
-- ============================================

-- Routes (Enhanced)
CREATE TABLE routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    route_name VARCHAR(255) NOT NULL,
    route_code VARCHAR(50),
    description TEXT,
    route_type ENUM('morning', 'afternoon', 'both') DEFAULT 'both',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    distance_km DECIMAL(8, 2),
    estimated_duration_minutes INT,
    active BOOLEAN DEFAULT TRUE,
    academic_year_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE SET NULL,
    INDEX idx_school (school_id),
    INDEX idx_route_name (route_name),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Route Stops (Enhanced)
CREATE TABLE route_stops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    stop_name VARCHAR(255) NOT NULL,
    stop_code VARCHAR(50),
    address TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    stop_order INT NOT NULL,
    estimated_arrival_time TIME,
    wait_time_minutes INT DEFAULT 2,
    is_pickup BOOLEAN DEFAULT TRUE,
    is_dropoff BOOLEAN DEFAULT TRUE,
    landmark TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    INDEX idx_route (route_id),
    INDEX idx_stop_order (route_id, stop_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bus-Route Assignments (Enhanced with schedules)
CREATE TABLE bus_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    route_id INT NOT NULL,
    academic_year_id INT,
    semester_id INT,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'all') DEFAULT 'all',
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE SET NULL,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL,
    UNIQUE KEY unique_bus_route_schedule (bus_id, route_id, day_of_week, academic_year_id),
    INDEX idx_bus (bus_id),
    INDEX idx_route (route_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ENHANCED STUDENT MANAGEMENT
-- ============================================

-- Students (Enhanced)
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    student_code VARCHAR(50),
    student_name VARCHAR(255) NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    grade VARCHAR(50),
    section VARCHAR(50),
    student_id_number VARCHAR(100),
    photo_url VARCHAR(500),
    medical_conditions TEXT,
    allergies TEXT,
    medications TEXT,
    emergency_medical_info TEXT,
    parent_id INT NOT NULL,
    assigned_bus_id INT,
    assigned_stop_id INT,
    assigned_route_id INT,
    pickup_time TIME,
    dropoff_time TIME,
    status ENUM('active', 'inactive', 'graduated', 'transferred') DEFAULT 'active',
    enrollment_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_bus_id) REFERENCES buses(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_stop_id) REFERENCES route_stops(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_route_id) REFERENCES routes(id) ON DELETE SET NULL,
    UNIQUE KEY unique_school_student_code (school_id, student_code),
    INDEX idx_school (school_id),
    INDEX idx_parent (parent_id),
    INDEX idx_bus (assigned_bus_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Emergency Contacts
CREATE TABLE emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    relationship VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    alternate_phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    is_primary BOOLEAN DEFAULT FALSE,
    can_pickup BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ATTENDANCE TRACKING
-- ============================================

-- Attendance Records
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    bus_id INT NOT NULL,
    route_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_type ENUM('pickup', 'dropoff') NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    check_in_time TIME,
    check_in_latitude DECIMAL(10, 8),
    check_in_longitude DECIMAL(11, 8),
    checked_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (checked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance (student_id, bus_id, attendance_date, attendance_type),
    INDEX idx_student (student_id),
    INDEX idx_date (attendance_date),
    INDEX idx_bus (bus_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- GPS TRACKING (Enhanced)
-- ============================================

-- GPS Logs (Enhanced)
CREATE TABLE gps_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    speed DECIMAL(5, 2) DEFAULT 0.00,
    heading DECIMAL(5, 2),
    altitude DECIMAL(8, 2),
    accuracy DECIMAL(8, 2),
    battery_level INT,
    signal_strength INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    INDEX idx_bus (bus_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_bus_timestamp (bus_id, timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- NOTIFICATIONS (Enhanced)
-- ============================================

-- Notifications (Enhanced with multiple channels)
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    recipient_type ENUM('user', 'student', 'bus', 'route', 'all') NOT NULL,
    recipient_id INT,
    bus_id INT,
    route_id INT,
    message TEXT NOT NULL,
    notification_type ENUM('traffic', 'speed_warning', 'nearby', 'route_change', 'general', 'emergency', 'attendance', 'maintenance', 'delay') NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    channels JSON, -- ['email', 'sms', 'push', 'in_app']
    email_sent BOOLEAN DEFAULT FALSE,
    sms_sent BOOLEAN DEFAULT FALSE,
    push_sent BOOLEAN DEFAULT FALSE,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE SET NULL,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE SET NULL,
    INDEX idx_school (school_id),
    INDEX idx_recipient (recipient_type, recipient_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DRIVER SCHEDULES
-- ============================================

-- Driver Schedules
CREATE TABLE driver_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    bus_id INT NOT NULL,
    route_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    shift_type ENUM('morning', 'afternoon', 'full_day') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    academic_year_id INT,
    semester_id INT,
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE SET NULL,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL,
    INDEX idx_driver (driver_id),
    INDEX idx_bus (bus_id),
    INDEX idx_route (route_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- REPORTS & ANALYTICS
-- ============================================

-- Report Templates
CREATE TABLE report_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    report_type ENUM('attendance', 'route', 'bus', 'driver', 'student', 'maintenance', 'custom') NOT NULL,
    template_config JSON,
    is_default BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_school (school_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generated Reports
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    template_id INT,
    report_name VARCHAR(255) NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    date_range_start DATE,
    date_range_end DATE,
    filters JSON,
    file_path VARCHAR(500),
    file_format ENUM('pdf', 'excel', 'csv', 'json') DEFAULT 'pdf',
    status ENUM('pending', 'generating', 'completed', 'failed') DEFAULT 'pending',
    generated_by INT,
    generated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES report_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_school (school_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SETTINGS & CONFIGURATION
-- ============================================

-- System Settings
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    category VARCHAR(50),
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_setting (school_id, setting_key),
    INDEX idx_school (school_id),
    INDEX idx_key (setting_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AUDIT LOGS
-- ============================================

-- Activity Logs
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    changes JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_school (school_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INTEGRATIONS
-- ============================================

-- SMS/Email Integration Settings
CREATE TABLE integration_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    integration_type ENUM('sms', 'email', 'push', 'payment') NOT NULL,
    provider VARCHAR(100),
    api_key VARCHAR(500),
    api_secret VARCHAR(500),
    config JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_integration (school_id, integration_type),
    INDEX idx_school (school_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Insert default super admin (for system management)
INSERT INTO schools (school_name, school_code, email, status, subscription_plan) 
VALUES ('System Default', 'SYS001', 'admin@system.com', 'active', 'enterprise');

-- Insert default admin user for first school
INSERT INTO users (school_id, email, password, full_name, role, status) 
VALUES (1, 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'active');

