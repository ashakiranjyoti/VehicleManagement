<?php
include '../config/database.php';
checkAuth();

if (!isset($_GET['id'])) {
    header("Location: view_vehicles.php");
    exit();
}

$vehicle_id = $_GET['id'];

// Check if vehicle exists
$sql = "SELECT * FROM vehicles WHERE id = $vehicle_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Vehicle not found!";
    header("Location: view_vehicles.php");
    exit();
}

// Check if vehicle has services
$service_check = "SELECT COUNT(*) as service_count FROM services WHERE vehicle_id = $vehicle_id";
$service_result = $conn->query($service_check);
$service_data = $service_result->fetch_assoc();

if ($service_data['service_count'] > 0) {
    $_SESSION['error'] = "Cannot delete vehicle with existing service records!";
    header("Location: view_vehicles.php");
    exit();
}

// Delete vehicle
$delete_sql = "DELETE FROM vehicles WHERE id = $vehicle_id";
if ($conn->query($delete_sql) {
    $_SESSION['success'] = "Vehicle deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting vehicle: " . $conn->error;
}

header("Location: view_vehicles.php");
exit();
?>