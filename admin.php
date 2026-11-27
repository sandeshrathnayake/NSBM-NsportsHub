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

     $mailSent = sendStatusEmail($req_info['email'], $req_info['name'], 'Club Membership', $req_info['club_name'], 'approved');
        $mailNote = $mailSent ? ' & notification email sent!' : ' (email delivery skipped).';
        $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Club membership approved' . $mailNote];

    } elseif ($action === 'reject' && $req_info) {
        $update_stmt = $conn->prepare("UPDATE user_clubs SET status = 'rejected' WHERE id = ?");
        $update_stmt->bind_param("i", $request_id);
        $update_stmt->execute();

        $mailSent = sendStatusEmail($req_info['email'], $req_info['name'], 'Club Membership', $req_info['club_name'], 'rejected');
        $mailNote = $mailSent ? ' & notification email sent!' : ' (email delivery skipped).';
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Club membership rejected' . $mailNote];
    }

    $redirect_url = 'admin.php?view=approvals' . ($search !== '' ? '&search=' . urlencode($search) : '');
    header('Location: ' . $redirect_url);
    exit();
}

if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['type']) && $_GET['type'] === 'booking') {
    $booking_id = intval($_GET['id']);
    $action = $_GET['action'];

    $b_info_stmt = $conn->prepare("
        SELECT u.name, u.email, fb.facility_name, fb.booking_date, fb.time_slot 
        FROM facility_bookings fb 
        JOIN users u ON fb.user_id = u.id 
        WHERE fb.id = ?
    ");
    $b_info_stmt->bind_param("i", $booking_id);
    $b_info_stmt->execute();
    $book_info = $b_info_stmt->get_result()->fetch_assoc();

    if ($action === 'approve' && $book_info) {
        $update_stmt = $conn->prepare("UPDATE facility_bookings SET status = 'approved' WHERE id = ?");
        $update_stmt->bind_param("i", $booking_id);
        $update_stmt->execute();

        $details = date('M d, Y', strtotime($book_info['booking_date'])) . ' | ' . $book_info['time_slot'];
        $mailSent = sendStatusEmail($book_info['email'], $book_info['name'], 'Facility Booking', $book_info['facility_name'], 'approved', $details);
        $mailNote = $mailSent ? ' & notification email sent!' : ' (email delivery skipped).';
        $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Facility booking approved' . $mailNote];

    } elseif ($action === 'reject' && $book_info) {
        $update_stmt = $conn->prepare("UPDATE facility_bookings SET status = 'rejected' WHERE id = ?");
        $update_stmt->bind_param("i", $booking_id);
        $update_stmt->execute();

        $details = date('M d, Y', strtotime($book_info['booking_date'])) . ' | ' . $book_info['time_slot'];
        $mailSent = sendStatusEmail($book_info['email'], $book_info['name'], 'Facility Booking', $book_info['facility_name'], 'rejected', $details);
        $mailNote = $mailSent ? ' & notification email sent!' : ' (email delivery skipped).';
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Facility booking rejected' . $mailNote];
    }

    $redirect_url = 'admin.php?view=bookings' . ($search !== '' ? '&search=' . urlencode($search) : '');
    header('Location: ' . $redirect_url);
    exit();
}

$search_param = '%' . $search . '%';

$requests_sql = "
    SELECT uc.id, u.name, u.email, uc.club_name, uc.joined_at 
    FROM user_clubs uc 
    JOIN users u ON uc.user_id = u.id 
    WHERE uc.status = 'pending'
";
if ($search !== '') {
    $requests_sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR uc.club_name LIKE ?)";
    $req_stmt = $conn->prepare($requests_sql . " ORDER BY uc.joined_at DESC");
    $req_stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $req_stmt->execute();
    $requests_result = $req_stmt->get_result();
} else {
    $requests_result = $conn->query($requests_sql . " ORDER BY uc.joined_at DESC");
}

$clubs_sql = "
    SELECT DISTINCT club_name, COUNT(user_id) as member_count 
    FROM user_clubs 
    WHERE status = 'approved'
";
if ($search !== '') {
    $clubs_sql .= " AND club_name LIKE ?";
    $club_stmt = $conn->prepare($clubs_sql . " GROUP BY club_name");
    $club_stmt->bind_param("s", $search_param);
    $club_stmt->execute();
    $clubs_result = $club_stmt->get_result();
} else {
    $clubs_result = $conn->query($clubs_sql . " GROUP BY club_name");
}

$bookings_sql = "
    SELECT fb.id, u.name, u.email, fb.facility_name, fb.booking_date, fb.time_slot, fb.status 
    FROM facility_bookings fb 
    JOIN users u ON fb.user_id = u.id 
    WHERE fb.status = 'pending'
";
if ($search !== '') {
    $bookings_sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR fb.facility_name LIKE ?)";
    $book_stmt = $conn->prepare($bookings_sql . " ORDER BY fb.booking_date ASC");
    $book_stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $book_stmt->execute();
    $bookings_result = $book_stmt->get_result();
} else {
    $bookings_result = $conn->query($bookings_sql . " ORDER BY fb.booking_date ASC");
}

$schedules_list = $conn->query("SELECT * FROM matches_schedules ORDER BY event_date DESC");
?>

