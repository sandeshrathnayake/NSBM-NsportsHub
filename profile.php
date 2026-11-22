<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

$name = $user['name'] ?? $_SESSION['name'] ?? 'User';
$email = $user['email'] ?? $_SESSION['email'] ?? 'Not set';
$role = $user['role'] ?? $_SESSION['role'] ?? 'student';

$club_stmt = $conn->prepare("SELECT club_name FROM user_clubs WHERE user_id = ? AND status = 'approved'");
$clubs_result = false;
if ($club_stmt) {
    $club_stmt->bind_param("i", $user_id);
    $club_stmt->execute();
    $clubs_result = $club_stmt->get_result();
}
?>
