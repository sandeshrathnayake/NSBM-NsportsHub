<?php
session_start();
require_once 'config.php';
require_once 'mail_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$user_stmt = $conn->prepare("SELECT role, name FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

if (!$user_data || ($user_data['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$admin_name = $user_data['name'] ?? 'Admin';
$view = $_GET['view'] ?? 'dashboard';
$search = trim($_GET['search'] ?? '');

$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']);

if (isset($_POST['add_schedule_btn'])) {
    $title      = trim($_POST['title']);
    $sport_type = trim($_POST['sport_type']);
    $event_type = trim($_POST['event_type']);
    $event_date = trim($_POST['event_date']);
    $event_time = trim($_POST['event_time']);
    $venue      = trim($_POST['venue']);

    $stmt = $conn->prepare("INSERT INTO matches_schedules (title, sport_type, event_type, event_date, event_time, venue, status) VALUES (?, ?, ?, ?, ?, ?, 'UPCOMING')");
    $stmt->bind_param("ssssss", $title, $sport_type, $event_type, $event_date, $event_time, $venue);
    
    if ($stmt->execute()) {
        $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'New match schedule added successfully!'];
    } else {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Failed to add schedule.'];
    }

 header('Location: admin.php?view=schedules');
    exit();
}

if (isset($_GET['action']) && isset($_GET['id']) && !isset($_GET['type'])) {
    $request_id = intval($_GET['id']);
    $action = $_GET['action'];

    $info_stmt = $conn->prepare("
        SELECT u.name, u.email, uc.club_name 
        FROM user_clubs uc 
        JOIN users u ON uc.user_id = u.id 
        WHERE uc.id = ?
    ");
    $info_stmt->bind_param("i", $request_id);
    $info_stmt->execute();
    $req_info = $info_stmt->get_result()->fetch_assoc();

    if ($action === 'approve' && $req_info) {
        $update_stmt = $conn->prepare("UPDATE user_clubs SET status = 'approved' WHERE id = ?");
        $update_stmt->bind_param("i", $request_id);
        $update_stmt->execute();
