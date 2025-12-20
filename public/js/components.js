/**
 * Reusable UI Components
 * 
 * Modern, flexible components for the application
 * 
 * @author Dana Baradie
 * @course IT404
 */

// Component System
class Component {
    constructor(element) {
        this.element = element;
        this.init();
    }
    
    init() {
        // Override in subclasses
    }
}

// Stat Card Component
class StatCard extends Component {
    constructor(element, data) {
        super(element);
        this.data = data;
        this.render();
    }
    
    render() {
        this.element.innerHTML = `
            <div class="stats-card-modern">
                <div class="stat-icon">
                    <i class="${this.data.icon}"></i>
                </div>
                <div class="stat-label">${this.data.label}</div>
                <div class="stat-value" style="color: ${this.data.color || '#0d6efd'}">
                    ${this.data.value}
                </div>
                ${this.data.change ? `
                    <div class="stat-change ${this.data.change > 0 ? 'text-success' : 'text-danger'}">
                        <i class="fas fa-arrow-${this.data.change > 0 ? 'up' : 'down'}"></i>
                        ${Math.abs(this.data.change)}%
                    </div>
                ` : ''}
            </div>
        `;
    }
}

// Bus Card Component
class BusCard extends Component {
    constructor(element, bus) {
        super(element);
        this.bus = bus;
        this.render();
    }
    
    render() {
        const statusClass = this.bus.status === 'active' ? 'success' : 'secondary';
        const hasGPS = this.bus.current_latitude && this.bus.current_longitude;
        
        this.element.innerHTML = `
            <div class="card bus-card" data-bus-id="${this.bus.id}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-bus me-2"></i>${this.bus.bus_number}
                    </h6>
                    <span class="badge bg-${statusClass}">
                        ${this.bus.status}
                    </span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Driver</small>
                        <div class="fw-bold">${this.bus.driver_name || 'Unassigned'}</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <small class="text-muted">Capacity</small>
                            <div>${this.bus.capacity} students</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">GPS</small>
                            <div>
                                ${hasGPS ? 
                                    '<span class="badge bg-success"><i class="fas fa-check"></i> Active</span>' :
                                    '<span class="badge bg-secondary"><i class="fas fa-times"></i> Offline</span>'
                                }
                            </div>
                        </div>
                    </div>
                    ${this.bus.speed ? `
                        <div class="speed-indicator">
                            <i class="fas fa-tachometer-alt"></i>
                            ${this.bus.speed.toFixed(1)} km/h
                        </div>
                    ` : ''}
                    <button class="btn btn-sm btn-primary w-100 mt-3" onclick="viewBusDetails(${this.bus.id})">
                        <i class="fas fa-eye me-1"></i>View Details
                    </button>
                </div>
            </div>
        `;
    }
}

// Notification Component
class NotificationItem extends Component {
    constructor(element, notification) {
        super(element);
        this.notification = notification;
        this.render();
    }
    
    render() {
        const typeIcons = {
            'nearby': 'fa-map-marker-alt',
            'speed_warning': 'fa-exclamation-triangle',
            'traffic': 'fa-traffic-light',
            'route_change': 'fa-route',
            'general': 'fa-bell'
        };
        
        const typeColors = {
            'nearby': 'success',
            'speed_warning': 'warning',
            'traffic': 'danger',
            'route_change': 'info',
            'general': 'primary'
        };
        
        const icon = typeIcons[this.notification.notification_type] || 'fa-bell';
        const color = typeColors[this.notification.notification_type] || 'primary';
        const unreadClass = !this.notification.is_read ? 'unread' : '';
        
        this.element.innerHTML = `
            <div class="notification-item ${unreadClass}" data-notification-id="${this.notification.id}">
                <div class="d-flex align-items-start">
                    <div class="notification-icon bg-${color}">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="notification-header">
                            <span class="badge bg-${color}">${this.notification.notification_type}</span>
                            <small class="text-muted ms-2">${this.notification.created_at_formatted}</small>
                        </div>
                        <div class="notification-message">${this.notification.message}</div>
                        ${this.notification.bus_number ? `
                            <div class="notification-bus">
                                <i class="fas fa-bus me-1"></i>Bus ${this.notification.bus_number}
                            </div>
                        ` : ''}
                    </div>
                    ${!this.notification.is_read ? `
                        <button class="btn btn-sm btn-outline-primary" onclick="markNotificationRead(${this.notification.id})">
                            <i class="fas fa-check"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }
}

// Table Component with Sorting
class DataTable extends Component {
    constructor(element, options = {}) {
        super(element);
        this.options = options;
        this.data = [];
        this.sortColumn = null;
        this.sortDirection = 'asc';
    }
    
    setData(data) {
        this.data = data;
        this.render();
    }
    
    render() {
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
        `;
        
        this.options.columns.forEach(col => {
            const sortable = col.sortable !== false ? 'sortable' : '';
            html += `
                <th class="${sortable}" data-column="${col.key}" onclick="${sortable ? `tableComponent.sort('${col.key}')` : ''}">
                    ${col.label}
                    ${col.sortable !== false ? '<i class="fas fa-sort ms-2"></i>' : ''}
                </th>
            `;
        });
        
        html += `
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        this.data.forEach(row => {
            html += '<tr>';
            this.options.columns.forEach(col => {
                const value = col.render ? col.render(row[col.key], row) : row[col.key];
                html += `<td>${value}</td>`;
            });
            html += '</tr>';
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        this.element.innerHTML = html;
    }
    
    sort(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }
        
        this.data.sort((a, b) => {
            const aVal = a[column];
            const bVal = b[column];
            
            if (this.sortDirection === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
        
        this.render();
    }
}

// Export components
window.StatCard = StatCard;
window.BusCard = BusCard;
window.NotificationItem = NotificationItem;
window.DataTable = DataTable;

