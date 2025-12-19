-- Seed Data for School Bus Tracking System
-- Author: Dana Baradie
-- Course: IT404

-- Insert sample drivers
INSERT INTO users (email, password, full_name, phone, role) VALUES
('driver1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', '961-3-123456', 'driver'),
('driver2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', '961-3-234567', 'driver'),
('driver3@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael Brown', '961-3-345678', 'driver');

-- Insert sample parents
INSERT INTO users (email, password, full_name, phone, role) VALUES
('parent1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma Wilson', '961-3-456789', 'parent'),
('parent2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Lee', '961-3-567890', 'parent'),
('parent3@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa Anderson', '961-3-678901', 'parent');

-- Insert sample buses
INSERT INTO buses (bus_number, license_plate, capacity, driver_id, status) VALUES
('BUS-001', 'ABC-123', 50, (SELECT id FROM users WHERE email = 'driver1@school.com'), 'active'),
('BUS-002', 'DEF-456', 45, (SELECT id FROM users WHERE email = 'driver2@school.com'), 'active'),
('BUS-003', 'GHI-789', 55, (SELECT id FROM users WHERE email = 'driver3@school.com'), 'active');

-- Insert sample routes
INSERT INTO routes (route_name, description, start_time, end_time, active) VALUES
('Route A - Downtown', 'Main route covering downtown Beirut area', '07:00:00', '08:30:00', TRUE),
('Route B - Achrafieh', 'Route covering Achrafieh and surrounding areas', '07:15:00', '08:45:00', TRUE),
('Route C - Zahle', 'Route covering Zahle and eastern suburbs', '06:45:00', '08:15:00', TRUE);

-- Insert route stops for Route A
INSERT INTO route_stops (route_id, stop_name, latitude, longitude, stop_order, estimated_arrival_time) VALUES
((SELECT id FROM routes WHERE route_name = 'Route A - Downtown'), 'Downtown Central', 33.8886, 35.4955, 1, '07:00:00'),
((SELECT id FROM routes WHERE route_name = 'Route A - Downtown'), 'Hamra Street', 33.8969, 35.4822, 2, '07:15:00'),
((SELECT id FROM routes WHERE route_name = 'Route A - Downtown'), 'Raouche', 33.8883, 35.4750, 3, '07:30:00'),
((SELECT id FROM routes WHERE route_name = 'Route A - Downtown'), 'Corniche', 33.8944, 35.4828, 4, '07:45:00'),
((SELECT id FROM routes WHERE route_name = 'Route A - Downtown'), 'School Gate', 33.9000, 35.4900, 5, '08:00:00');

-- Insert route stops for Route B
INSERT INTO route_stops (route_id, stop_name, latitude, longitude, stop_order, estimated_arrival_time) VALUES
((SELECT id FROM routes WHERE route_name = 'Route B - Achrafieh'), 'Achrafieh Center', 33.9010, 35.5300, 1, '07:15:00'),
((SELECT id FROM routes WHERE route_name = 'Route B - Achrafieh'), 'Sassine Square', 33.9050, 35.5250, 2, '07:30:00'),
((SELECT id FROM routes WHERE route_name = 'Route B - Achrafieh'), 'Monot Street', 33.8950, 35.5200, 3, '07:45:00'),
((SELECT id FROM routes WHERE route_name = 'Route B - Achrafieh'), 'School Gate', 33.9000, 35.4900, 4, '08:00:00');

-- Insert route stops for Route C
INSERT INTO route_stops (route_id, stop_name, latitude, longitude, stop_order, estimated_arrival_time) VALUES
((SELECT id FROM routes WHERE route_name = 'Route C - Zahle'), 'Zahle Center', 33.8547, 35.8623, 1, '06:45:00'),
((SELECT id FROM routes WHERE route_name = 'Route C - Zahle'), 'Chtaura', 33.8200, 35.8500, 2, '07:00:00'),
((SELECT id FROM routes WHERE route_name = 'Route C - Zahle'), 'Jounieh', 33.9800, 35.6100, 3, '07:30:00'),
((SELECT id FROM routes WHERE route_name = 'Route C - Zahle'), 'School Gate', 33.9000, 35.4900, 4, '08:00:00');

-- Assign buses to routes
INSERT INTO bus_routes (bus_id, route_id) VALUES
((SELECT id FROM buses WHERE bus_number = 'BUS-001'), (SELECT id FROM routes WHERE route_name = 'Route A - Downtown')),
((SELECT id FROM buses WHERE bus_number = 'BUS-002'), (SELECT id FROM routes WHERE route_name = 'Route B - Achrafieh')),
((SELECT id FROM buses WHERE bus_number = 'BUS-003'), (SELECT id FROM routes WHERE route_name = 'Route C - Zahle'));

-- Insert sample students
INSERT INTO students (student_name, parent_id, grade, assigned_stop_id) VALUES
('Alex Wilson', (SELECT id FROM users WHERE email = 'parent1@school.com'), 'Grade 5', 
 (SELECT id FROM route_stops WHERE stop_name = 'Downtown Central' LIMIT 1)),
('Sophie Wilson', (SELECT id FROM users WHERE email = 'parent1@school.com'), 'Grade 3', 
 (SELECT id FROM route_stops WHERE stop_name = 'Downtown Central' LIMIT 1)),
('James Lee', (SELECT id FROM users WHERE email = 'parent2@school.com'), 'Grade 7', 
 (SELECT id FROM route_stops WHERE stop_name = 'Achrafieh Center' LIMIT 1)),
('Maya Anderson', (SELECT id FROM users WHERE email = 'parent3@school.com'), 'Grade 4', 
 (SELECT id FROM route_stops WHERE stop_name = 'Zahle Center' LIMIT 1));

-- Insert sample GPS logs (recent tracking data)
INSERT INTO gps_logs (bus_id, latitude, longitude, speed, heading, timestamp) VALUES
((SELECT id FROM buses WHERE bus_number = 'BUS-001'), 33.8886, 35.4955, 45.50, 90.00, DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
((SELECT id FROM buses WHERE bus_number = 'BUS-001'), 33.8900, 35.4970, 50.00, 95.00, DATE_SUB(NOW(), INTERVAL 4 MINUTE)),
((SELECT id FROM buses WHERE bus_number = 'BUS-001'), 33.8920, 35.4990, 48.75, 100.00, DATE_SUB(NOW(), INTERVAL 3 MINUTE)),
((SELECT id FROM buses WHERE bus_number = 'BUS-002'), 33.9010, 35.5300, 42.30, 85.00, DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
((SELECT id FROM buses WHERE bus_number = 'BUS-002'), 33.9025, 35.5320, 45.00, 88.00, DATE_SUB(NOW(), INTERVAL 4 MINUTE)),
((SELECT id FROM buses WHERE bus_number = 'BUS-003'), 33.8547, 35.8623, 55.20, 180.00, DATE_SUB(NOW(), INTERVAL 5 MINUTE));

-- Update bus current locations
UPDATE buses SET 
    current_latitude = 33.8920,
    current_longitude = 35.4990,
    last_location_update = NOW()
WHERE bus_number = 'BUS-001';

UPDATE buses SET 
    current_latitude = 33.9025,
    current_longitude = 35.5320,
    last_location_update = NOW()
WHERE bus_number = 'BUS-002';

UPDATE buses SET 
    current_latitude = 33.8547,
    current_longitude = 35.8623,
    last_location_update = NOW()
WHERE bus_number = 'BUS-003';

-- Insert sample notifications
INSERT INTO notifications (parent_id, bus_id, message, notification_type, is_read) VALUES
((SELECT id FROM users WHERE email = 'parent1@school.com'), 
 (SELECT id FROM buses WHERE bus_number = 'BUS-001'),
 'Bus BUS-001 is approaching your stop. Estimated arrival: 5 minutes.', 'nearby', FALSE),
((SELECT id FROM users WHERE email = 'parent2@school.com'), 
 (SELECT id FROM buses WHERE bus_number = 'BUS-002'),
 'Route B has been updated. Please check the new schedule.', 'route_change', FALSE),
((SELECT id FROM users WHERE email = 'parent3@school.com'), 
 (SELECT id FROM buses WHERE bus_number = 'BUS-003'),
 'Traffic alert: Bus may be delayed by 10-15 minutes due to heavy traffic.', 'traffic', FALSE);

