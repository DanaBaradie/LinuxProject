#!/bin/bash
# Script to move all API files to public directory
# Run this on your server

cd /var/www/bus-tracking

echo "Moving API files to public directory..."

# Create public/api directories
mkdir -p public/api/auth
mkdir -p public/api/buses
mkdir -p public/api/routes
mkdir -p public/api/gps
mkdir -p public/api/notifications

# Copy API files (keeping originals as backup)
echo "Copying API files..."
cp -r backend/api/* public/api/

# Update paths in copied files
echo "Updating paths in API files..."

# Update config paths (from ../../config to ../../../config)
find public/api -name "*.php" -type f -exec sed -i 's|__DIR__ \. \x27/../../config|__DIR__ . \x27/../../../config|g' {} \;
find public/api -name "*.php" -type f -exec sed -i 's|__DIR__ \. \x27/../../middleware|__DIR__ . \x27/../../../includes/middleware|g' {} \;
find public/api -name "*.php" -type f -exec sed -i 's|__DIR__ \. \x27/../../services|__DIR__ . \x27/../../../app/Services|g' {} \;

# Move middleware to includes
echo "Moving middleware..."
cp backend/middleware/auth.php includes/middleware.php

# Update config.php to work from public
echo "Configuring paths..."

echo ""
echo "✅ Files moved to public/api/"
echo ""
echo "Next steps:"
echo "1. Test API endpoints: http://your-domain/api/auth/login.php"
echo "2. Update web server document root to point to /var/www/bus-tracking/public"
echo "3. Update JavaScript API_BASE to '/api'"
echo "4. Remove backend/api/ directory after testing (or keep as backup)"
echo ""

