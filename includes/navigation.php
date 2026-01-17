<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="col-md-3 col-lg-2 d-md-block sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo (in_array($current_page, ['add_vehicle.php', 'view_vehicles.php', 'edit_vehicle.php'])) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/vehicles/view_vehicles.php">
                    <i class="fas fa-motorcycle"></i> Vehicles
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo (in_array($current_page, ['add_service.php', 'view_services.php', 'service_history.php'])) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/services/service_history.php">
                    <i class="fas fa-tools"></i> Services
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'manage_tools.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/manage_tools.php">
                    <i class="fas fa-tools"></i> Tool Management
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'manage_engineers.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/manage_engineers.php">
                    <i class="fas fa-user-tie"></i> Manage Engineers
                </a>
            </li>
            <li class="nav-item">
                        <a class="nav-link"  href="<?php echo BASE_URL; ?>/tool_tracking.php">
                            <i class="fas fa-search"></i> Tool Tracking
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"  href="<?php echo BASE_URL; ?>/tool_report.php">
                            <i class="fas fa-chart-pie"></i> Tool Reports
                        </a>
                    </li>

            <li hidden class="nav-item dropdown">
                <a class="nav-link dropdown-toggle <?php echo (in_array($current_page, ['monthly_report.php', 'cost_analysis.php', 'advanced_reports.php', 'service_reminders.php', 'financial_reports.php'])) ? 'active' : ''; ?>"
                    href="#" data-bs-toggle="dropdown">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <ul class="dropdown-menu">
                    <li><a hidden class="dropdown-item" href="<?php echo BASE_URL; ?>/reports/monthly_report.php">Monthly Reports</a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/services/view_services.php"> Reports</a></li>
                    <li><a hidden class="dropdown-item" href="<?php echo BASE_URL; ?>/reports/registration_report.php">
                            Registration Number Report
                        </a></li>
                    <li class="nav-item">
                        <a class="nav-link" style=" color:black " href="<?php echo BASE_URL; ?>/tool_tracking.php">
                            <i class="fas fa-search"></i> Tool Tracking
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style=" color:black " href="<?php echo BASE_URL; ?>/tool_report.php">
                            <i class="fas fa-chart-pie"></i> Tool Reports
                        </a>
                    </li>
                    <!-- <li><a class="dropdown-item" href="../reports/cost_analysis.php">Cost Analysis</a></li>
                    <li><a class="dropdown-item" href="../reports/financial_reports.php">Financial Reports</a></li>
                    <li><a class="dropdown-item" href="../reports/advanced_reports.php">Advanced Analytics</a></li>
                    <li><a class="dropdown-item" href="../reports/service_reminders.php">Service Reminders</a></li> -->
                </ul>
            </li>

            <?php if (isAdmin()): ?>
                <li class="nav-item mt-3">
                    <!-- <small class="text-muted px-3">ADMIN</small> -->
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/users/manage_users.php">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>