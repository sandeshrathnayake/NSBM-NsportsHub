<?php
session_start();
require_once 'config.php';

$user_id = $_SESSION['user_id'] ?? null;
$name = $_SESSION['name'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    if (!$user_id) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Please login to submit a facility booking!'];
        header('Location: facility_booking.php');
        exit();
    }

    $facility_name = trim($_POST['facility_name'] ?? '');
    $booking_date  = trim($_POST['booking_date'] ?? '');
    $time_slot     = trim($_POST['time_slot'] ?? '');
    $purpose       = trim($_POST['purpose'] ?? '');

    $allowed_facilities = [
        'Athletics Track & Field Grid',
        'Badminton Court A (Indoor)',
        'Badminton Court B (Indoor)',
        'Chess Gear Set & Board',
        'Main Cricket Pitch & Nets',
        'Martial Arts Dojo Room',
        'Netball Court (Outdoor)',
        'Powerlifting Gym Section',
        'Rugby Training Ground',
        'Swimming Pool (Lap Lanes)',
        'Table Tennis Gear & Arena',
        'Tennis Court A (Outdoor)',
        'Volleyball Court'
    ];

    $allowed_slots = [
        '08:00 - 10:00',
        '10:00 - 12:00',
        '12:00 - 14:00',
        '14:00 - 16:00',
        '16:00 - 18:00',
        '18:00 - 20:00'
    ];

    if (!in_array($facility_name, $allowed_facilities, true) || 
        !in_array($time_slot, $allowed_slots, true) || 
        empty($booking_date) || empty($purpose)) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Please fill in all fields with valid options!'];
    } elseif ($booking_date < date('Y-m-d')) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Booking date cannot be in the past!'];
    } else {
        $check = $conn->prepare("
            SELECT id FROM facility_bookings 
            WHERE facility_name = ? AND booking_date = ? AND time_slot = ? 
            AND status IN ('pending', 'approved')
        ");
        $check->bind_param("sss", $facility_name, $booking_date, $time_slot);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'That facility is already reserved for the selected date and time slot!'];
        } else {
            $stmt = $conn->prepare("
                INSERT INTO facility_bookings (user_id, facility_name, booking_date, time_slot, purpose, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("issss", $user_id, $facility_name, $booking_date, $time_slot, $purpose);
            
            if ($stmt->execute()) {
                $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Booking request submitted! Waiting for admin approval.'];
            } else {
                $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Failed to process request. Please try again.'];
            }
        }
    }

     header('Location: facility_booking.php');
    exit();
}

$user_bookings = [];
if ($user_id) {
    $stmt = $conn->prepare("
        SELECT * FROM facility_bookings 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Bookings - NSBM SportsHub</title>
    <link rel="stylesheet" href="style.css?v=9.9">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="profile-page-body">

    <header>
        <a href="index.php" class="logo">
            <img src="logo2.png" alt="Logo">
        </a>
        <nav>
            <a href="index.php">Home</a>
            <a href="sports_clubs.php">Sports Clubs</a>
            <a href="schedules.php">Schedules & Matches</a>
            <a href="facility_booking.php" style="color: #0abf30;">Facility Bookings</a>
        </nav>





