<?php
/**
 * Admin Live Tracking Page
 * 
 * Real-time bus tracking with Google Maps integration
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();
requireRole('admin');

require_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-map-marked-alt me-2"></i>Live Bus Tracking</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshMap()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Live Tracking:</strong> Map updates automatically every 5 seconds. Click on bus markers for details.
            </div>

            <!-- Map Container -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <div id="busMap" class="map-container"></div>
                </div>
            </div>

            <!-- Bus List -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Active Buses</h5>
                </div>
                <div class="card-body">
                    <div id="busList" class="table-responsive">
                        <p class="text-center text-muted">
                            <span class="loading-spinner"></span> Loading buses...
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Include Maps JavaScript -->
<script src="/frontend/js/maps.js"></script>
<script src="/frontend/js/app.js"></script>

<script>
// Initialize map manager
let mapManager;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    const apiKey = '<?php echo GOOGLE_MAPS_API_KEY; ?>';
    mapManager = new BusMapManager('busMap', {
        apiKey: apiKey,
        updateInterval: 5000,
        autoUpdate: true
    });
    
    // Load bus list
    loadBusList();
    
    // Refresh bus list every 5 seconds
    setInterval(loadBusList, 5000);
});

function refreshMap() {
    if (mapManager) {
        mapManager.loadBuses();
        loadBusList();
        showToast('Map refreshed', 'success');
    }
}

async function loadBusList() {
    try {
        const response = await fetch('/backend/api/buses');
        const data = await response.json();
        
        if (data.success && data.data.buses) {
            displayBusList(data.data.buses);
        } else {
            document.getElementById('busList').innerHTML = 
                '<p class="text-center text-muted">No buses found</p>';
        }
    } catch (error) {
        console.error('Error loading buses:', error);
        document.getElementById('busList').innerHTML = 
            '<p class="text-center text-danger">Error loading buses</p>';
    }
}

function displayBusList(buses) {
    const activeBuses = buses.filter(bus => bus.status === 'active');
    
    if (activeBuses.length === 0) {
        document.getElementById('busList').innerHTML = 
            '<p class="text-center text-muted">No active buses</p>';
        return;
    }
    
    let html = `
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Bus Number</th>
                    <th>Driver</th>
                    <th>Location</th>
                    <th>Speed</th>
                    <th>Last Update</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    activeBuses.forEach(bus => {
        const hasLocation = bus.current_latitude && bus.current_longitude;
        const locationStatus = hasLocation 
            ? `<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Tracking</span>`
            : `<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>No GPS</span>`;
        
        const location = hasLocation 
            ? `${parseFloat(bus.current_latitude).toFixed(6)}, ${parseFloat(bus.current_longitude).toFixed(6)}`
            : 'N/A';
        
        const speed = bus.speed ? `${parseFloat(bus.speed).toFixed(1)} km/h` : 'N/A';
        const lastUpdate = bus.last_location_update_formatted || bus.last_update || 'Never';
        
        html += `
            <tr>
                <td><strong>${escapeHtml(bus.bus_number)}</strong></td>
                <td>${escapeHtml(bus.driver_name || 'Unassigned')}</td>
                <td>
                    ${location}
                    ${hasLocation ? `<br><small class="text-muted">${locationStatus}</small>` : ''}
                </td>
                <td>${speed}</td>
                <td><small>${lastUpdate}</small></td>
                <td>
                    <span class="badge bg-${bus.status === 'active' ? 'success' : 'secondary'}">
                        ${bus.status.charAt(0).toUpperCase() + bus.status.slice(1)}
                    </span>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    document.getElementById('busList').innerHTML = html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once '../../includes/footer.php'; ?>

