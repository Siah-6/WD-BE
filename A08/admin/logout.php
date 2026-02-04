<?php
session_start();
session_destroy();

// Log the logout activity
require_once '../connect.php';

function logActivity($adminID, $action, $table, $recordID, $oldValues, $newValues, $ip) {
    global $conn;
    $query = "INSERT INTO admin_activity_log (adminID, action, table_name, record_id, old_values, new_values, ip_address) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issssss", $adminID, $action, $table, $recordID, $oldValues, $newValues, $ip);
    mysqli_stmt_execute($stmt);
}

// Log logout if session exists
if (isset($_SESSION['admin_id'])) {
    logActivity($_SESSION['admin_id'], 'logout', 'admin', $_SESSION['admin_id'], 
                json_encode(['username' => $_SESSION['admin_username']]), null, $_SERVER['REMOTE_ADDR']);
}

header('Location: login.php');
exit();
?>
