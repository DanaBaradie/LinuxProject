/**
 * Enhanced Real-Time Tracking System
 * 
 * Professional tracking interface with animations and real-time updates
 * 
 * @author Dana Baradie
 * @course IT404
 */

class EnhancedTrackingSystem {
    constructor(mapContainerId, options = {}) {
        this.mapContainerId = mapContainerId;
        this.map = null;
        this.markers = {};
        this.polylines = {}; // Route paths
        this.infoWindows = {};
        this.apiKey = options.apiKey || '';
        this.updateInterval = options.updateInterval || 3000; // 3 seconds
        this.buses = [];
        this.selectedBus = null;
        this.autoUpdate = true;
        this.showRoutes = options.showRoutes !== false;
        this.showHistory = options.showHistory || false;
        
        this.init();
    }
    
    /**
     * Initialize tracking system
     */
    async init() {
        await this.loadGoogleMaps();
        this.setupEventListeners();
        this.startAutoUpdate();
        this.loadInitialData();
    }
    
    /**
     * Load Google Maps
     */
    loadGoogleMaps() {
        return new Promise((resolve, reject) => {
            if (window.google && window.google.maps) {
                this.initMap();
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKey}&libraries=geometry,drawing`;
            script.async = true;
            script.defer = true;
            script.onload = () => {
                this.initMap();
                resolve();
            };
            script.onerror = () => {
                console.warn('Google Maps failed to load, using mock mode');
                this.initMockMap();
                resolve();
            };
            document.head.appendChild(script);
        });
    }
    
    /**
     * Initialize Google Maps
     */
    initMap() {
        const container = document.getElementById(this.mapContainerId);
        if (!container) return;
        
        const defaultCenter = { lat: 33.8886, lng: 35.4955 };
        
        this.map = new google.maps.Map(container, {
            zoom: 13,
            center: defaultCenter,
            mapTypeId: 'roadmap',
            styles: this.getMapStyles(),
            disableDefaultUI: false,
            zoomControl: true,
            mapTypeControl: true,
            scaleControl: true,
            streetViewControl: false,
            rotateControl: false,
            fullscreenControl: true
        });
        
        // Add custom controls
        this.addMapControls();
    }
    
    /**
     * Get custom map styles
     */
    getMapStyles() {
        return [
            {
                featureType: 'poi',
                elementType: 'labels',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'transit',
                elementType: 'labels',
                stylers: [{ visibility: 'off' }]
            }
        ];
    }
    
    /**
     * Add custom map controls
     */
    addMapControls() {
        // Refresh button
        const refreshControl = document.createElement('div');
        refreshControl.className = 'map-control';
        refreshControl.innerHTML = '<button class="btn btn-sm btn-primary"><i class="fas fa-sync-alt"></i></button>';
        refreshControl.title = 'Refresh';
        refreshControl.addEventListener('click', () => this.refresh());
        
        // Fit bounds button
        const fitBoundsControl = document.createElement('div');
        fitBoundsControl.className = 'map-control';
        fitBoundsControl.innerHTML = '<button class="btn btn-sm btn-info"><i class="fas fa-expand"></i></button>';
        fitBoundsControl.title = 'Fit All Buses';
        fitBoundsControl.addEventListener('click', () => this.fitAllBuses());
        
        // Toggle routes button
        const routesControl = document.createElement('div');
        routesControl.className = 'map-control';
        routesControl.innerHTML = '<button class="btn btn-sm btn-success"><i class="fas fa-route"></i></button>';
        routesControl.title = 'Toggle Routes';
        routesControl.addEventListener('click', () => this.toggleRoutes());
        
        this.map.controls[google.maps.ControlPosition.TOP_RIGHT].push(refreshControl);
        this.map.controls[google.maps.ControlPosition.TOP_RIGHT].push(fitBoundsControl);
        this.map.controls[google.maps.ControlPosition.TOP_RIGHT].push(routesControl);
    }
    
    /**
     * Load initial bus data
     */
    async loadInitialData() {
        try {
            const response = await fetch('/api/gps/live.php');
            const data = await response.json();
            
            if (data.success && data.data.buses) {
                this.buses = data.data.buses;
                this.updateMap();
                this.updateBusList();
            }
        } catch (error) {
            console.error('Error loading buses:', error);
            this.showError('Failed to load bus data');
        }
    }
    
    /**
     * Update map with bus locations
     */
    updateMap() {
        if (!this.map) return;
        
        const bounds = new google.maps.LatLngBounds();
        let hasValidLocation = false;
        
        this.buses.forEach(bus => {
            if (!bus.location || !bus.location.latitude || !bus.location.longitude) {
                return;
            }
            
            hasValidLocation = true;
            const position = {
                lat: parseFloat(bus.location.latitude),
                lng: parseFloat(bus.location.longitude)
            };
            
            bounds.extend(position);
            
            // Update or create marker
            if (this.markers[bus.id]) {
                // Animate marker movement
                this.animateMarker(this.markers[bus.id], position);
            } else {
                this.createMarker(bus, position);
            }
            
            // Update route if enabled
            if (this.showRoutes && bus.route_id) {
                this.updateRoutePath(bus);
            }
        });
        
        // Fit bounds if we have locations
        if (hasValidLocation && this.buses.length > 0) {
            if (this.buses.length === 1) {
                this.map.setCenter(bounds.getCenter());
                this.map.setZoom(15);
            } else {
                this.map.fitBounds(bounds);
            }
        }
    }
    
    /**
     * Create bus marker
     */
    createMarker(bus, position) {
        const icon = this.getBusIcon(bus);
        
        const marker = new google.maps.Marker({
            position: position,
            map: this.map,
            icon: icon,
            title: `Bus ${bus.bus_number}`,
            animation: google.maps.Animation.DROP,
            zIndex: 1000
        });
        
        // Create info window
        const infoWindow = new google.maps.InfoWindow({
            content: this.getInfoWindowContent(bus)
        });
        
        marker.addListener('click', () => {
            // Close other info windows
            Object.values(this.infoWindows).forEach(iw => iw.close());
            infoWindow.open(this.map, marker);
            this.selectBus(bus.id);
        });
        
        this.markers[bus.id] = marker;
        this.infoWindows[bus.id] = infoWindow;
    }
    
    /**
     * Get bus icon based on status
     */
    getBusIcon(bus) {
        const status = bus.status || 'active';
        const colors = {
            'active': '#28a745',
            'inactive': '#6c757d',
            'maintenance': '#ffc107'
        };
        
        const color = colors[status] || '#0d6efd';
        
        return {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 10,
            fillColor: color,
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 3,
            anchor: new google.maps.Point(0, 0)
        };
    }
    
    /**
     * Animate marker movement
     */
    animateMarker(marker, newPosition) {
        const oldPosition = marker.getPosition();
        const distance = google.maps.geometry.spherical.computeDistanceBetween(
            oldPosition,
            newPosition
        );
        
        // Only animate if moved significantly (>10 meters)
        if (distance > 10) {
            marker.setAnimation(google.maps.Animation.BOUNCE);
            setTimeout(() => marker.setAnimation(null), 750);
        }
        
        marker.setPosition(newPosition);
    }
    
    /**
     * Get info window content
     */
    getInfoWindowContent(bus) {
        const speed = bus.speed ? `${bus.speed.toFixed(1)} km/h` : 'N/A';
        const lastUpdate = bus.last_update || 'Unknown';
        const statusColor = bus.status === 'active' ? 'success' : 'secondary';
        
        return `
            <div style="min-width: 250px; padding: 10px;">
                <h6 style="margin: 0 0 10px 0; color: #0d6efd;">
                    <i class="fas fa-bus"></i> Bus ${bus.bus_number}
                </h6>
                <div style="font-size: 12px; line-height: 1.8;">
                    <div><strong>Driver:</strong> ${bus.driver_name || 'Unassigned'}</div>
                    <div><strong>Speed:</strong> ${speed}</div>
                    <div><strong>Status:</strong> 
                        <span class="badge bg-${statusColor}">${bus.status || 'Unknown'}</span>
                    </div>
                    <div><strong>Last Update:</strong><br><small>${lastUpdate}</small></div>
                    ${bus.heading ? `<div><strong>Heading:</strong> ${bus.heading.toFixed(0)}°</div>` : ''}
                </div>
                <button class="btn btn-sm btn-primary mt-2 w-100" onclick="trackingSystem.focusBus(${bus.id})">
                    <i class="fas fa-crosshairs"></i> Focus
                </button>
            </div>
        `;
    }
    
    /**
     * Update route path on map
     */
    updateRoutePath(bus) {
        // This would load route stops and draw polyline
        // Implementation depends on route data structure
    }
    
    /**
     * Update bus list sidebar
     */
    updateBusList() {
        const listContainer = document.getElementById('bus-list-container');
        if (!listContainer) return;
        
        let html = '';
        
        this.buses.forEach(bus => {
            const hasLocation = bus.location && bus.location.latitude;
            const statusClass = bus.status === 'active' ? 'success' : 'secondary';
            const isSelected = this.selectedBus === bus.id ? 'selected' : '';
            
            html += `
                <div class="bus-list-item ${isSelected}" data-bus-id="${bus.id}" onclick="trackingSystem.selectBus(${bus.id})">
                    <div class="d-flex align-items-center">
                        <div class="bus-indicator bg-${statusClass}"></div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-bold">${bus.bus_number}</div>
                            <small class="text-muted">${bus.driver_name || 'No driver'}</small>
                        </div>
                        <div class="text-end">
                            ${hasLocation ? 
                                `<span class="badge bg-success"><i class="fas fa-circle"></i></span>` :
                                `<span class="badge bg-secondary"><i class="fas fa-circle"></i></span>`
                            }
                        </div>
                    </div>
                    ${bus.speed ? `<div class="mt-2"><small>Speed: ${bus.speed.toFixed(1)} km/h</small></div>` : ''}
                </div>
            `;
        });
        
        listContainer.innerHTML = html;
    }
    
    /**
     * Select bus
     */
    selectBus(busId) {
        this.selectedBus = busId;
        const bus = this.buses.find(b => b.id === busId);
        
        if (bus && this.markers[busId]) {
            this.map.setCenter(this.markers[busId].getPosition());
            this.map.setZoom(16);
            this.infoWindows[busId].open(this.map, this.markers[busId]);
        }
        
        this.updateBusList();
    }
    
    /**
     * Focus on specific bus
     */
    focusBus(busId) {
        this.selectBus(busId);
    }
    
    /**
     * Fit all buses in view
     */
    fitAllBuses() {
        const bounds = new google.maps.LatLngBounds();
        let hasBounds = false;
        
        Object.values(this.markers).forEach(marker => {
            bounds.extend(marker.getPosition());
            hasBounds = true;
        });
        
        if (hasBounds) {
            this.map.fitBounds(bounds);
        }
    }
    
    /**
     * Toggle route display
     */
    toggleRoutes() {
        this.showRoutes = !this.showRoutes;
        // Update route polylines visibility
    }
    
    /**
     * Refresh data
     */
    async refresh() {
        this.showLoading();
        await this.loadInitialData();
        this.hideLoading();
        this.showToast('Data refreshed', 'success');
    }
    
    /**
     * Start auto-update
     */
    startAutoUpdate() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
        
        this.updateTimer = setInterval(() => {
            this.loadInitialData();
        }, this.updateInterval);
    }
    
    /**
     * Stop auto-update
     */
    stopAutoUpdate() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'r' && e.ctrlKey) {
                e.preventDefault();
                this.refresh();
            }
        });
    }
    
    /**
     * Show loading state
     */
    showLoading() {
        const loadingEl = document.getElementById('tracking-loading');
        if (loadingEl) loadingEl.style.display = 'block';
    }
    
    /**
     * Hide loading state
     */
    hideLoading() {
        const loadingEl = document.getElementById('tracking-loading');
        if (loadingEl) loadingEl.style.display = 'none';
    }
    
    /**
     * Show error message
     */
    showError(message) {
        if (typeof showToast === 'function') {
            showToast(message, 'error');
        } else {
            alert(message);
        }
    }
    
    /**
     * Show toast
     */
    showToast(message, type = 'info') {
        if (typeof showToast === 'function') {
            showToast(message, type);
        }
    }
}

// Initialize tracking system
let trackingSystem;

document.addEventListener('DOMContentLoaded', () => {
    const apiKey = '<?php echo defined("GOOGLE_MAPS_API_KEY") ? GOOGLE_MAPS_API_KEY : ""; ?>';
    trackingSystem = new EnhancedTrackingSystem('tracking-map', {
        apiKey: apiKey,
        updateInterval: 3000,
        showRoutes: true
    });
});

