<?php
include '../config/database.php';
checkAuth();

if (!isset($_GET['id'])) {
    header("Location: view_services.php");
    exit();
}

$service_id = $_GET['id'];

// Fetch service details to get bill file name
$sql = "SELECT * FROM services WHERE id = $service_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Service record not found!";
    header("Location: view_services.php");
    exit();
}

$service = $result->fetch_assoc();

// Delete bill file if exists
if ($service['bill_file'] && file_exists("../uploads/" . $service['bill_file'])) {
    unlink("../uploads/" . $service['bill_file']);
}

// Delete service record
$delete_sql = "DELETE FROM services WHERE id = $service_id";
if ($conn->query($delete_sql)) {
    $_SESSION['success'] = "Service record deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting service record: " . $conn->error;
}

header("Location: view_services.php");
exit();
?>