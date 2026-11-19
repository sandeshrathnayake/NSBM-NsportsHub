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






