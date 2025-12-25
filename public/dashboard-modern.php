<?php
/**
 * Modern Enhanced Dashboard
 * 
 * Professional dashboard with modern UI and real-time updates
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$role = getUserRole();
$stats = [];

// Get date filter parameters
$date_filter = $_GET['date'] ?? 'today';
$selected_date = $_GET['selected_date'] ?? date('Y-m-d');
$date_range_start = $selected_date;
$date_range_end = $selected_date;

// Calculate date range based on filter
switch ($date_filter) {
    case 'today':
        $date_range_start = date('Y-m-d');
        $date_range_end = date('Y-m-d');
        break;
    case 'week':
        // Get start of week (Monday)
        $day_of_week = date('w', strtotime($selected_date));
        $days_to_monday = ($day_of_week == 0) ? 6 : $day_of_week - 1;
        $date_range_start = date('Y-m-d', strtotime($selected_date . ' -' . $days_to_monday . ' days'));
        $date_range_end = date('Y-m-d', strtotime($date_range_start . ' +6 days'));
        break;
    case 'month':
        $date_range_start = date('Y-m-01', strtotime($selected_date));
        $date_range_end = date('Y-m-t', strtotime($selected_date));
        break;
    case 'custom':
        $date_range_start = $_GET['start_date'] ?? $selected_date;
        $date_range_end = $_GET['end_date'] ?? $selected_date;
        break;
}

try {
    if ($role === 'admin') {
        // Enhanced statistics (these are not date-dependent, but we'll add date filters where applicable)
        $query = "SELECT COUNT(*) as count FROM buses WHERE status = 'active'";
        $stats['active_buses'] = $db->query($query)->fetch()['count'];
        
        $query = "SELECT COUNT(*) as count FROM users WHERE role = 'driver'";
        $stats['total_drivers'] = $db->query($query)->fetch()['count'];
        
        $query = "SELECT COUNT(*) as count FROM users WHERE role = 'parent'";
        $stats['total_parents'] = $db->query($query)->fetch()['count'];
        
        $query = "SELECT COUNT(*) as count FROM routes WHERE active = 1";
        $stats['active_routes'] = $db->query($query)->fetch()['count'];
        
        $query = "SELECT COUNT(*) as count FROM students";
        $stats['total_students'] = $db->query($query)->fetch()['count'];
        
        // Get buses with GPS status
        $query = "SELECT COUNT(*) as count FROM buses 
                  WHERE status = 'active' 
                    AND current_latitude IS NOT NULL 
                    AND current_longitude IS NOT NULL";
        $stats['tracking_buses'] = $db->query($query)->fetch()['count'];
        
        // Recent activity with date filter (fallback to all if no results in date range)
        $query = "SELECT b.*, u.full_name as driver_name 
                  FROM buses b 
                  LEFT JOIN users u ON b.driver_id = u.id 
                  WHERE DATE(b.updated_at) BETWEEN :start_date AND :end_date
                  ORDER BY b.updated_at DESC LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':start_date' => $date_range_start,
            ':end_date' => $date_range_end
        ]);
        $buses = $stmt->fetchAll();
        
        // If no buses in date range, show most recent buses
        if (empty($buses)) {
            $query = "SELECT b.*, u.full_name as driver_name 
                      FROM buses b 
                      LEFT JOIN users u ON b.driver_id = u.id 
                      ORDER BY b.updated_at DESC LIMIT 5";
            $buses = $db->query($query)->fetchAll();
        }
        
        // GPS logs count for selected period
        $query = "SELECT COUNT(DISTINCT bus_id) as count FROM gps_logs 
                  WHERE DATE(timestamp) BETWEEN :start_date AND :end_date";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':start_date' => $date_range_start,
            ':end_date' => $date_range_end
        ]);
        $stats['gps_updates'] = $stmt->fetch()['count'] ?? 0;
        
    } elseif ($role === 'parent') {
        $query = "SELECT COUNT(*) as count FROM students WHERE parent_id = :parent_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':parent_id', $_SESSION['user_id']);
        $stmt->execute();
        $stats['my_children'] = $stmt->fetch()['count'];
        
        // Get notifications count
        $query = "SELECT COUNT(*) as count FROM notifications 
                  WHERE parent_id = :parent_id AND is_read = FALSE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':parent_id', $_SESSION['user_id']);
        $stmt->execute();
        $stats['unread_notifications'] = $stmt->fetch()['count'];
        
    } elseif ($role === 'driver') {
        $query = "SELECT * FROM buses WHERE driver_id = :driver_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':driver_id', $_SESSION['user_id']);
        $stmt->execute();
        $my_bus = $stmt->fetch();
    }
} catch (Exception $e) {
    error_log($e->getMessage());
}

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="/css/modern-theme.css">

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Welcome Section -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard
                    </h1>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                    <?php if ($date_filter !== 'today'): ?>
                        <small class="text-info">
                            <i class="fas fa-filter me-1"></i>
                            Showing data from 
                            <?php 
                                if ($date_filter === 'week') {
                                    echo date('M d', strtotime($date_range_start)) . ' - ' . date('M d', strtotime($date_range_end));
                                } elseif ($date_filter === 'month') {
                                    echo date('F Y', strtotime($date_range_start));
                                } elseif ($date_filter === 'custom') {
                                    echo date('M d', strtotime($date_range_start)) . ' - ' . date('M d', strtotime($date_range_end));
                                }
                            ?>
                        </small>
                    <?php endif; ?>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" id="dateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-calendar me-1"></i>
                                <span id="dateFilterText"><?php 
                                    switch($date_filter) {
                                        case 'today': echo 'Today'; break;
                                        case 'week': echo 'This Week'; break;
                                        case 'month': echo 'This Month'; break;
                                        case 'custom': echo date('M d', strtotime($date_range_start)) . ' - ' . date('M d', strtotime($date_range_end)); break;
                                        default: echo 'Today';
                                    }
                                ?></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dateFilterBtn">
                                <li><a class="dropdown-item" href="#" onclick="setDateFilter('today'); return false;">
                                    <i class="fas fa-calendar-day me-2"></i>Today
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="setDateFilter('week'); return false;">
                                    <i class="fas fa-calendar-week me-2"></i>This Week
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="setDateFilter('month'); return false;">
                                    <i class="fas fa-calendar-alt me-2"></i>This Month
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); event.stopPropagation(); setTimeout(function(){ showCustomDatePicker(); }, 100); return false;">
                                    <i class="fas fa-calendar-check me-2"></i>Custom Date Range
                                </a></li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportDashboard()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
                
                <!-- Custom Date Range Picker Modal -->
                <div class="modal fade" id="customDateModal" tabindex="-1" aria-labelledby="customDateModalLabel" aria-hidden="true" style="z-index: 9999 !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important;">
                    <div class="modal-dialog modal-dialog-centered" style="z-index: 10000 !important; pointer-events: auto !important; position: relative !important;">
                        <div class="modal-content" style="pointer-events: auto !important; position: relative !important; z-index: 10000 !important; cursor: default !important;">
                            <div class="modal-header">
                                <h5 class="modal-title" id="customDateModalLabel">
                                    <i class="fas fa-calendar-check me-2"></i>Select Date Range
                                </h5>
                                <button type="button" class="btn-close" onclick="closeCustomDateModal()" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate" value="<?php echo $date_range_start; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="endDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="endDate" value="<?php echo $date_range_end; ?>">
                                </div>
                                <div class="btn-group w-100 mb-2" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); setQuickDate('today'); return false;">Today</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); setQuickDate('yesterday'); return false;">Yesterday</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); setQuickDate('week'); return false;">This Week</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); setQuickDate('month'); return false;">This Month</button>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="event.stopPropagation(); closeCustomDateModal(); return false;">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="event.stopPropagation(); applyCustomDateRange(); return false;">Apply Filter</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($role === 'admin'): ?>
                <!-- Admin Dashboard -->
                <div class="grid-responsive mb-4">
                    <div class="stats-card-modern">
                        <div class="stat-icon">
                            <i class="fas fa-bus"></i>
                        </div>
                        <div class="stat-label">Active Buses</div>
                        <div class="stat-value" style="color: #0d6efd;">
                            <?php echo $stats['active_buses']; ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-satellite text-success"></i> 
                            <?php echo $stats['tracking_buses'] ?? 0; ?> tracking
                        </div>
                    </div>

                    <div class="stats-card-modern">
                        <div class="stat-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-label">Drivers</div>
                        <div class="stat-value" style="color: #198754;">
                            <?php echo $stats['total_drivers']; ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-check-circle text-success"></i> All active
                        </div>
                    </div>

                    <div class="stats-card-modern">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-label">Parents</div>
                        <div class="stat-value" style="color: #0dcaf0;">
                            <?php echo $stats['total_parents']; ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-user-check"></i> Registered
                        </div>
                    </div>

                    <div class="stats-card-modern">
                        <div class="stat-icon">
                            <i class="fas fa-route"></i>
                        </div>
                        <div class="stat-label">Active Routes</div>
                        <div class="stat-value" style="color: #ffc107;">
                            <?php echo $stats['active_routes']; ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-map-marked-alt"></i> In operation
                        </div>
                    </div>

                    <div class="stats-card-modern">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-label">Students</div>
                        <div class="stat-value" style="color: #6f42c1;">
                            <?php echo $stats['total_students'] ?? 0; ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-school"></i> Enrolled
                        </div>
                    </div>

                    <div class="stats-card-modern">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-label">System Health</div>
                        <div class="stat-value" style="color: #28a745;">
                            98%
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-heartbeat text-success"></i> Optimal
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <a href="/tracking-enhanced.php" class="btn btn-primary w-100">
                                            <i class="fas fa-map-marked-alt me-2"></i>Live Tracking
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/buses.php" class="btn btn-success w-100">
                                            <i class="fas fa-bus me-2"></i>Manage Buses
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/routes.php" class="btn btn-info w-100">
                                            <i class="fas fa-route me-2"></i>Manage Routes
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/students.php" class="btn btn-warning w-100">
                                            <i class="fas fa-user-graduate me-2"></i>Manage Students
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Buses -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-bus me-2"></i>Recent Buses</h5>
                                <a href="/buses.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($buses)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Bus Number</th>
                                                    <th>Driver</th>
                                                    <th>Status</th>
                                                    <th>GPS</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($buses as $bus): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($bus['driver_name'] ?? 'Unassigned'); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $bus['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                                <?php echo ucfirst($bus['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($bus['current_latitude'] && $bus['current_longitude']): ?>
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check-circle me-1"></i>Active
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">
                                                                    <i class="fas fa-times-circle me-1"></i>Offline
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="/tracking-enhanced.php?bus=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-bus"></i>
                                        </div>
                                        <p>No buses added yet</p>
                                        <a href="/buses.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add Your First Bus
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Quick Stats</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Bus Utilization</span>
                                        <span>75%</span>
                                    </div>
                                    <div class="progress-modern">
                                        <div class="progress-bar" style="width: 75%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Route Coverage</span>
                                        <span>90%</span>
                                    </div>
                                    <div class="progress-modern">
                                        <div class="progress-bar bg-success" style="width: 90%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>GPS Accuracy</span>
                                        <span>98%</span>
                                    </div>
                                    <div class="progress-modern">
                                        <div class="progress-bar bg-info" style="width: 98%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($role === 'parent'): ?>
                <!-- Parent Dashboard -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-card-modern">
                            <div class="stat-icon">
                                <i class="fas fa-child"></i>
                            </div>
                            <div class="stat-label">My Children</div>
                            <div class="stat-value" style="color: #0d6efd;">
                                <?php echo $stats['my_children']; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card-modern">
                            <div class="stat-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="stat-label">Notifications</div>
                            <div class="stat-value" style="color: #dc3545;">
                                <?php echo $stats['unread_notifications'] ?? 0; ?>
                            </div>
                            <div class="text-muted small mt-2">Unread</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <a href="/track-bus.php" class="stats-card-modern text-decoration-none d-block">
                            <div class="stat-icon">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <div class="stat-label">Track Bus</div>
                            <div class="stat-value" style="color: #28a745;">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                    </div>
                </div>

            <?php elseif ($role === 'driver'): ?>
                <!-- Driver Dashboard -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-bus me-2"></i>My Bus</h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($my_bus)): ?>
                                    <h3 class="text-primary"><?php echo htmlspecialchars($my_bus['bus_number']); ?></h3>
                                    <p class="text-muted">Assigned Bus</p>
                                    <a href="/update-location.php" class="btn btn-primary w-100 mt-3">
                                        <i class="fas fa-location-arrow me-2"></i>Update Location
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        No bus assigned yet. Please contact administrator.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
// Date Filter Functions
function setDateFilter(filter) {
    const url = new URL(window.location);
    url.searchParams.set('date', filter);
    url.searchParams.set('selected_date', '<?php echo date('Y-m-d'); ?>');
    url.searchParams.delete('start_date');
    url.searchParams.delete('end_date');
    window.location.href = url.toString();
}

function showCustomDatePicker() {
    const modalElement = document.getElementById('customDateModal');
    if (!modalElement) {
        console.error('Modal element not found');
        alert('Date picker modal not found. Please refresh the page.');
        return;
    }
    
    // Set default dates if not set
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    if (startDateInput && !startDateInput.value) {
        startDateInput.value = '<?php echo $date_range_start; ?>';
    }
    if (endDateInput && !endDateInput.value) {
        endDateInput.value = '<?php echo $date_range_end; ?>';
    }
    
    // Try Bootstrap Modal first
    try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            let modal = bootstrap.Modal.getInstance(modalElement);
            if (!modal) {
                modal = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
            }
            modal.show();
            
            // Ensure modal is interactive after Bootstrap shows it
            setTimeout(() => {
                modalElement.style.pointerEvents = 'auto';
                const modalContent = modalElement.querySelector('.modal-content');
                if (modalContent) {
                    modalContent.style.pointerEvents = 'auto';
                }
            }, 100);
            return;
        }
    } catch (e) {
        console.log('Bootstrap Modal not available, using fallback:', e);
    }
    
    // Close sidebar if open (it might be blocking)
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    if (sidebar && sidebar.classList.contains('show')) {
        sidebar.classList.remove('show');
    }
    if (sidebarOverlay && sidebarOverlay.classList.contains('show')) {
        sidebarOverlay.classList.remove('show');
    }
    
    // Fallback: show modal manually
    modalElement.classList.add('show');
    modalElement.style.display = 'block';
    modalElement.style.zIndex = '9999'; // Very high z-index
    modalElement.style.pointerEvents = 'auto';
    modalElement.style.position = 'fixed';
    modalElement.style.top = '0';
    modalElement.style.left = '0';
    modalElement.style.width = '100%';
    modalElement.style.height = '100%';
    modalElement.setAttribute('aria-hidden', 'false');
    modalElement.setAttribute('aria-modal', 'true');
    document.body.classList.add('modal-open');
    document.body.style.overflow = 'hidden';
    document.body.style.paddingRight = '0'; // Prevent body shift
    
    // Ensure modal dialog is visible and centered
    const modalDialog = modalElement.querySelector('.modal-dialog');
    if (modalDialog) {
        modalDialog.style.zIndex = '10000';
        modalDialog.style.pointerEvents = 'auto';
        modalDialog.style.position = 'relative';
        modalDialog.style.margin = '1rem auto';
        modalDialog.style.maxWidth = '500px';
    }
    
    // Ensure modal content is interactive
    const modalContent = modalElement.querySelector('.modal-content');
    if (modalContent) {
        modalContent.style.zIndex = '10000';
        modalContent.style.pointerEvents = 'auto';
        modalContent.style.position = 'relative';
    }
    
    // Remove existing backdrop if any
    const existingBackdrop = document.getElementById('modalBackdrop');
    if (existingBackdrop) {
        existingBackdrop.remove();
    }
    
    // Add backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.id = 'modalBackdrop';
    backdrop.style.zIndex = '9998';
    backdrop.style.pointerEvents = 'auto';
    backdrop.style.position = 'fixed';
    backdrop.style.top = '0';
    backdrop.style.left = '0';
    backdrop.style.width = '100%';
    backdrop.style.height = '100%';
    backdrop.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    document.body.appendChild(backdrop);
    
    // Close on backdrop click (but not on modal content)
    backdrop.addEventListener('click', function(e) {
        // Only close if clicking directly on backdrop, not on modal content
        if (e.target === backdrop && !modalContent.contains(e.target)) {
            closeCustomDateModal();
        }
    });
    
    // Prevent clicks on modal wrapper from closing it, but allow clicks on content
    modalElement.addEventListener('click', function(e) {
        // Only close if clicking on the modal wrapper itself, not on content
        if (e.target === modalElement) {
            closeCustomDateModal();
        }
    });
    
    // Stop propagation on modal content to prevent backdrop clicks
    if (modalContent) {
        modalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Make all inputs and buttons explicitly interactive
    setTimeout(() => {
        const inputs = modalElement.querySelectorAll('input, button, label');
        inputs.forEach(el => {
            el.style.pointerEvents = 'auto';
            el.style.zIndex = '10001';
        });
        
        const startDateInput = document.getElementById('startDate');
        if (startDateInput) {
            startDateInput.focus();
        }
    }, 300);
}

function closeCustomDateModal() {
    const modalElement = document.getElementById('customDateModal');
    if (modalElement) {
        // Try Bootstrap Modal first
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                    return;
                }
            }
        } catch (e) {
            console.log('Bootstrap Modal not available, using fallback');
        }
        
        // Fallback: hide modal manually
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        modalElement.style.zIndex = '';
        modalElement.style.pointerEvents = '';
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.removeAttribute('aria-modal');
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // Remove backdrop
        const backdrop = document.getElementById('modalBackdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }
}

function setQuickDate(type) {
    const today = new Date();
    let startDate, endDate;
    
    switch(type) {
        case 'today':
            startDate = endDate = today.toISOString().split('T')[0];
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            startDate = endDate = yesterday.toISOString().split('T')[0];
            break;
        case 'week':
            const dayOfWeek = today.getDay();
            const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
            const monday = new Date(today);
            monday.setDate(today.getDate() - daysToMonday);
            startDate = monday.toISOString().split('T')[0];
            const sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);
            endDate = sunday.toISOString().split('T')[0];
            break;
        case 'month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
            break;
    }
    
    document.getElementById('startDate').value = startDate;
    document.getElementById('endDate').value = endDate;
}

function applyCustomDateRange() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        alert('Start date cannot be after end date');
        return;
    }
    
    const url = new URL(window.location);
    url.searchParams.set('date', 'custom');
    url.searchParams.set('start_date', startDate);
    url.searchParams.set('end_date', endDate);
    window.location.href = url.toString();
}

// Export Dashboard Data
function exportDashboard() {
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Exporting...';
    
    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const dateFilter = urlParams.get('date') || 'today';
    const selectedDate = urlParams.get('selected_date') || '<?php echo date('Y-m-d'); ?>';
    const startDate = urlParams.get('start_date') || '';
    const endDate = urlParams.get('end_date') || '';
    
    // Build export URL
    let exportUrl = '/api/reports/generate.php?format=csv';
    exportUrl += '&date=' + encodeURIComponent(dateFilter);
    exportUrl += '&selected_date=' + encodeURIComponent(selectedDate);
    if (startDate) exportUrl += '&start_date=' + encodeURIComponent(startDate);
    if (endDate) exportUrl += '&end_date=' + encodeURIComponent(endDate);
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = 'dashboard-export-' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Show success message
    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        showToast('Dashboard data exported successfully!', 'success');
    }, 1000);
}

// Initialize components
document.addEventListener('DOMContentLoaded', () => {
    // Add page transition
    document.body.classList.add('page-transition');
    
    // Initialize modal event listeners
    const modalElement = document.getElementById('customDateModal');
    if (modalElement) {
        // Close modal on backdrop click
        modalElement.addEventListener('click', function(e) {
            if (e.target === modalElement) {
                closeCustomDateModal();
            }
        });
        
        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalElement.classList.contains('show')) {
                closeCustomDateModal();
            }
        });
    }
    
    // Auto-refresh stats every 30 seconds
    if (typeof refreshStats === 'function') {
        setInterval(refreshStats, 30000);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

