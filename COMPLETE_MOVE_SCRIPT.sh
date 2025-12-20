#!/bin/bash
# Complete script to move all files to public directory
# Run this on your server: bash COMPLETE_MOVE_SCRIPT.sh

cd /var/www/bus-tracking

echo "=========================================="
echo "Moving All Files to Public Directory"
echo "=========================================="
echo ""

# 1. Create all necessary directories in public
echo "Step 1: Creating directories..."
mkdir -p public/api/auth
mkdir -p public/api/buses
mkdir -p public/api/routes
mkdir -p public/api/gps
mkdir -p public/api/notifications
mkdir -p public/api/v1/attendance
mkdir -p public/api/v1/reports
mkdir -p public/api/v1/schools
mkdir -p public/css
mkdir -p public/js
mkdir -p public/assets
mkdir -p public/uploads
echo "✅ Directories created"

# 2. Copy all API files from backend to public
echo ""
echo "Step 2: Copying API files..."
if [ -d "backend/api" ]; then
    cp -r backend/api/* public/api/ 2>/dev/null || true
    echo "✅ API files copied"
else
    echo "⚠️  backend/api not found, skipping..."
fi

# 3. Copy API v1 files if they exist
if [ -d "api/v1" ]; then
    cp -r api/v1/* public/api/v1/ 2>/dev/null || true
    echo "✅ API v1 files copied"
fi

# 4. Move middleware
echo ""
echo "Step 3: Moving middleware..."
if [ -f "backend/middleware/auth.php" ]; then
    cp backend/middleware/auth.php includes/middleware.php
    echo "✅ Middleware moved"
fi

# 5. Update all paths in API files
echo ""
echo "Step 4: Updating paths in API files..."

# Update require_once paths
find public/api -name "*.php" -type f -exec sed -i "s|__DIR__ . '/../../config|__DIR__ . '/../../../config|g" {} \;
find public/api -name "*.php" -type f -exec sed -i "s|__DIR__ . '/../../middleware|__DIR__ . '/../../../includes/middleware|g" {} \;
find public/api -name "*.php" -type f -exec sed -i "s|__DIR__ . '/../../services|__DIR__ . '/../../../app/Services|g" {} \;

# Also handle require_once with different formats
find public/api -name "*.php" -type f -exec sed -i "s|require_once '../\.\./\.\./config|require_once '../../../config|g" {} \;
find public/api -name "*.php" -type f -exec sed -i "s|require_once '../\.\./\.\./middleware|require_once '../../../includes/middleware|g" {} \;
find public/api -name "*.php" -type f -exec sed -i "s|require_once '../\.\./\.\./services|require_once '../../../app/Services|g" {} \;

echo "✅ Paths updated"

# 6. Move CSS and JS if they're not already in public
echo ""
echo "Step 5: Organizing assets..."
if [ -d "frontend/css" ] && [ ! -d "public/css" ] || [ -z "$(ls -A public/css 2>/dev/null)" ]; then
    cp -r frontend/css/* public/css/ 2>/dev/null || true
    echo "✅ CSS files organized"
fi

if [ -d "frontend/js" ] && [ ! -d "public/js" ] || [ -z "$(ls -A public/js 2>/dev/null)" ]; then
    cp -r frontend/js/* public/js/ 2>/dev/null || true
    echo "✅ JS files organized"
fi

# 7. Update JavaScript API paths
echo ""
echo "Step 6: Updating JavaScript API paths..."
find public/js -name "*.js" -type f -exec sed -i "s|'/backend/api|'/api|g" {} \;
find public/js -name "*.js" -type f -exec sed -i 's|"/backend/api|"/api|g' {} \;
find public/js -name "*.js" -type f -exec sed -i "s|const API_BASE = '/backend/api|const API_BASE = '/api|g" {} \;
find public/js -name "*.js" -type f -exec sed -i "s|const API_BASE = \"/backend/api|const API_BASE = \"/api|g" {} \;
echo "✅ JavaScript paths updated"

# 8. Set proper permissions
echo ""
echo "Step 7: Setting permissions..."
chmod 755 -R public/
chmod 644 public/api/**/*.php 2>/dev/null || true
chmod 644 public/js/*.js 2>/dev/null || true
chmod 644 public/css/*.css 2>/dev/null || true
echo "✅ Permissions set"

# 9. Create .htaccess for public (Apache)
echo ""
echo "Step 8: Creating .htaccess..."
cat > public/.htaccess << 'HTACCESS'
# Enable rewrite engine
RewriteEngine On

# Redirect to index.php if file doesn't exist
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Protect config and includes
<FilesMatch "^(config|includes|app)">
    Order allow,deny
    Deny from all
</FilesMatch>
HTACCESS
echo "✅ .htaccess created"

echo ""
echo "=========================================="
echo "✅ Migration Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Update web server document root to: /var/www/bus-tracking/public"
echo "2. Test API: curl http://your-domain/api/auth/check.php"
echo "3. Test login page: http://your-domain/login.php"
echo ""
echo "Structure:"
echo "  public/          - All web-accessible files"
echo "  config/          - Config (not web accessible)"
echo "  includes/        - Includes (not web accessible)"
echo "  app/             - Services (not web accessible)"
echo "  database/        - SQL files (not web accessible)"
echo ""

