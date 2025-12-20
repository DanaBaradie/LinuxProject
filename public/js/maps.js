/**
 * Google Maps Integration with Mock GPS Fallback
 * 
 * Handles map initialization, bus markers, and real-time updates
 * 
 * @author Dana Baradie
 * @course IT404
 */

class BusMapManager {
    constructor(mapContainerId, options = {}) {
        this.mapContainerId = mapContainerId;
        this.map = null;
        this.markers = {};
        this.apiKey = options.apiKey || '';
        this.useMockGPS = !this.apiKey || this.apiKey === 'YOUR_API_KEY_HERE';
        this.updateInterval = options.updateInterval || 5000; // 5 seconds
        this.busId = options.busId || null;
        this.autoUpdate = options.autoUpdate !== false;
        
        this.init();
    }
    
    /**
     * Initialize the map
     */
    async init() {
        if (this.useMockGPS) {
            console.log('Using mock GPS - Google Maps API key not configured');
            this.initMockMap();
        } else {
            await this.loadGoogleMaps();
        }
        
        if (this.autoUpdate) {
            this.startAutoUpdate();
        }
    }
    
    /**
     * Load Google Maps API
     */
    loadGoogleMaps() {
        return new Promise((resolve, reject) => {
            if (window.google && window.google.maps) {
                this.initGoogleMap();
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKey}&libraries=geometry`;
            script.async = true;
            script.defer = true;
            script.onload = () => {
                this.initGoogleMap();
                resolve();
            };
            script.onerror = () => {
                console.error('Failed to load Google Maps API, falling back to mock GPS');
                this.useMockGPS = true;
                this.initMockMap();
                resolve();
            };
            document.head.appendChild(script);
        });
    }
    
    /**
     * Initialize Google Maps
     */
    initGoogleMap() {
        const container = document.getElementById(this.mapContainerId);
        if (!container) {
            console.error('Map container not found');
            return;
        }
        
        // Default center (Beirut, Lebanon)
        const defaultCenter = { lat: 33.8886, lng: 35.4955 };
        
        this.map = new google.maps.Map(container, {
            zoom: 13,
            center: defaultCenter,
            mapTypeId: 'roadmap',
            styles: [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ]
        });
        
        this.loadBuses();
    }
    
    /**
     * Initialize mock map (simple HTML/CSS based)
     */
    initMockMap() {
        const container = document.getElementById(this.mapContainerId);
        if (!container) {
            console.error('Map container not found');
            return;
        }
        
        container.innerHTML = `
            <div class="mock-map-container" style="
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                position: relative;
                border-radius: 8px;
                overflow: hidden;
            ">
                <div class="mock-map-overlay" style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-image: 
                        repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(255,255,255,0.1) 2px, rgba(255,255,255,0.1) 4px),
                        repeating-linear-gradient(90deg, transparent, transparent 2px, rgba(255,255,255,0.1) 2px, rgba(255,255,255,0.1) 4px);
                "></div>
                <div class="mock-bus-markers" style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                "></div>
                <div class="mock-map-info" style="
                    position: absolute;
                    top: 10px;
                    left: 10px;
                    background: rgba(0,0,0,0.7);
                    color: white;
                    padding: 10px;
                    border-radius: 5px;
                    font-size: 12px;
                ">
                    <i class="fas fa-info-circle"></i> Mock GPS Mode - Configure Google Maps API key for full map
                </div>
            </div>
        `;
        
        this.mockMapContainer = container.querySelector('.mock-bus-markers');
        this.loadBuses();
    }
    
    /**
     * Load buses and display on map
     */
    async loadBuses() {
        try {
            const url = this.busId 
                ? `/api/gps/live.php?bus_id=${this.busId}`
                : '/api/gps/live.php';
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.data.buses) {
                this.updateMarkers(data.data.buses);
            }
        } catch (error) {
            console.error('Error loading buses:', error);
        }
    }
    
    /**
     * Update markers on map
     */
    updateMarkers(buses) {
        if (this.useMockGPS) {
            this.updateMockMarkers(buses);
        } else {
            this.updateGoogleMarkers(buses);
        }
    }
    
    /**
     * Update Google Maps markers
     */
    updateGoogleMarkers(buses) {
        if (!this.map) return;
        
        // Remove old markers
        Object.values(this.markers).forEach(marker => marker.setMap(null));
        this.markers = {};
        
        // Add new markers
        buses.forEach(bus => {
            if (!bus.location || !bus.location.latitude || !bus.location.longitude) {
                return;
            }
            
            const position = {
                lat: parseFloat(bus.location.latitude),
                lng: parseFloat(bus.location.longitude)
            };
            
            const icon = {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="40" height="40" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="20" cy="20" r="18" fill="#0d6efd" stroke="white" stroke-width="2"/>
                        <text x="20" y="26" font-size="16" fill="white" text-anchor="middle" font-weight="bold">🚌</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(40, 40),
                anchor: new google.maps.Point(20, 20)
            };
            
            const marker = new google.maps.Marker({
                position: position,
                map: this.map,
                title: `Bus ${bus.bus_number}`,
                icon: icon,
                animation: google.maps.Animation.DROP
            });
            
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 10px;">
                        <h6 style="margin: 0 0 5px 0;"><strong>Bus ${bus.bus_number}</strong></h6>
                        <p style="margin: 0; font-size: 12px;">Driver: ${bus.driver_name || 'N/A'}</p>
                        <p style="margin: 0; font-size: 12px;">Status: ${bus.status}</p>
                        ${bus.speed ? `<p style="margin: 0; font-size: 12px;">Speed: ${bus.speed.toFixed(1)} km/h</p>` : ''}
                        <p style="margin: 0; font-size: 11px; color: #666;">Last update: ${bus.last_update || 'N/A'}</p>
                    </div>
                `
            });
            
            marker.addListener('click', () => {
                infoWindow.open(this.map, marker);
            });
            
            this.markers[bus.id] = marker;
        });
        
        // Fit bounds to show all markers
        if (buses.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            buses.forEach(bus => {
                if (bus.location && bus.location.latitude && bus.location.longitude) {
                    bounds.extend({
                        lat: parseFloat(bus.location.latitude),
                        lng: parseFloat(bus.location.longitude)
                    });
                }
            });
            this.map.fitBounds(bounds);
        }
    }
    
    /**
     * Update mock map markers
     */
    updateMockMarkers(buses) {
        if (!this.mockMapContainer) return;
        
        this.mockMapContainer.innerHTML = '';
        
        buses.forEach(bus => {
            if (!bus.location || !bus.location.latitude || !bus.location.longitude) {
                return;
            }
            
            // Convert GPS to mock map coordinates (simple projection)
            const lat = parseFloat(bus.location.latitude);
            const lng = parseFloat(bus.location.longitude);
            
            // Simple projection for Beirut area
            const centerLat = 33.8886;
            const centerLng = 35.4955;
            const scale = 10000; // pixels per degree
            
            const x = ((lng - centerLng) * scale) + 50;
            const y = ((centerLat - lat) * scale) + 50;
            
            const marker = document.createElement('div');
            marker.style.cssText = `
                position: absolute;
                left: ${x}px;
                top: ${y}px;
                width: 30px;
                height: 30px;
                background: #0d6efd;
                border: 3px solid white;
                border-radius: 50%;
                transform: translate(-50%, -50%);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                cursor: pointer;
            `;
            marker.innerHTML = '🚌';
            marker.title = `Bus ${bus.bus_number}`;
            
            this.mockMapContainer.appendChild(marker);
        });
    }
    
    /**
     * Start automatic updates
     */
    startAutoUpdate() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
        
        this.updateTimer = setInterval(() => {
            this.loadBuses();
        }, this.updateInterval);
    }
    
    /**
     * Stop automatic updates
     */
    stopAutoUpdate() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
    }
    
    /**
     * Update single bus location
     */
    async updateBusLocation(busId, latitude, longitude, speed = null, heading = null) {
        try {
            const response = await fetch('/api/gps/update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    bus_id: busId,
                    latitude: latitude,
                    longitude: longitude,
                    speed: speed,
                    heading: heading
                })
            });
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error updating bus location:', error);
            return { success: false, message: error.message };
        }
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BusMapManager;
}

