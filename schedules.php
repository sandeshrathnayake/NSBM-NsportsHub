<?php
session_start();
require_once 'config.php';

$upcoming_schedules = $conn->query("
    SELECT * FROM matches_schedules 
    WHERE event_date >= CURDATE()
    ORDER BY event_date ASC
");

$filter_type = $_GET['filter'] ?? 'all';
$selected_month = $_GET['month'] ?? date('Y-m');
$selected_date = $_GET['date'] ?? '';

$past_query = "SELECT * FROM matches_schedules WHERE event_date < CURDATE()";
if ($filter_type === 'month' && !empty($selected_month)) {
    $past_query .= " AND DATE_FORMAT(event_date, '%Y-%m') = '" . $conn->real_escape_string($selected_month) . "'";
} elseif ($filter_type === 'date' && !empty($selected_date)) {
    $past_query .= " AND event_date = '" . $conn->real_escape_string($selected_date) . "'";
} elseif ($filter_type === 'week') {
    $past_query .= " AND event_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
}
$past_query .= " ORDER BY event_date DESC";
$past_schedules = $conn->query($past_query);

$upcoming_bookings = $conn->query("
    SELECT fb.facility_name, fb.booking_date, fb.time_slot, u.name as booked_by
    FROM facility_bookings fb
    JOIN users u ON fb.user_id = u.id
    WHERE fb.status = 'approved' AND fb.booking_date >= CURDATE()
    ORDER BY fb.booking_date ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedules & Matches - NSBM SportsHub</title>
    <link rel="stylesheet" href="style.css?v=9.6">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body.profile-page-body {
            background: url('background.jpg') no-repeat center center fixed !important;
            background-size: cover !important;
            background-color: #0f172a !important;
        }
        body.profile-page-body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65) !important;
            z-index: -1;
        }

        .schedule-section-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 24px 28px;
            margin-bottom: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }
        .schedule-section-card h2 {
            font-size: 19px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 10px;
        }

        .admin-table th {
            color: #ffffff !important;
            font-weight: 600;
        }
        .admin-table td {
            color: #f1f5f9 !important;
        }
        .no-data {
            color: #94a3b8 !important;
            text-align: center;
            padding: 16px !important;
        }

        .filter-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
            background: rgba(255, 255, 255, 0.03);
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .filter-bar select, .filter-bar input {
            background: #1e293b;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13.5px;
            outline: none;
        }
        .filter-btn {
            background: #0abf30;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13.5px;
            transition: background 0.2s;
        }
        .filter-btn:hover {
            background: #059669;
        }
        .filter-reset {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
            margin-left: auto;
        }
        .filter-reset:hover {
            color: #fff;
            text-decoration: underline;
        }
        .table-scroll {
            max-height: 320px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="profile-page-body">

    <header>
        <a href="index.php" class="logo">
            <img src="logo2.png" alt="Logo">
        </a>
        <nav>
            <a href="index.php">Home</a>
            <a href="sports_clubs.php">Sports Clubs</a>
            <a href="schedules.php" style="color: #0abf30;">Schedules & Matches</a>
            <a href="facility_booking.php">Facility Bookings</a>
        </nav>
        <div class="user-auth">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="profile-box">
                    <div class="avatar-circle"><?= strtoupper($_SESSION['name'][0] ?? 'U') ?></div>
                    <div class="dropdown">
                        <a href="profile.php">My Account</a>
                        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <a href="admin.php">Admin Panel</a>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <button class="login-btn-modal">Login</button>
            <?php endif; ?>
        </div>
    </header>

    <section class="profile-section">
        <div style="max-width: 1050px; margin: 40px auto; padding: 0 20px;">
            
            <div class="schedule-section-card">
                <h2>Upcoming Matches & Training Fixtures</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Sport</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Venue</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($upcoming_schedules && $upcoming_schedules->num_rows > 0): ?>
                                <?php while ($row = $upcoming_schedules->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['sport_type']) ?></td>
                                        <td><span class="type-pill"><?= htmlspecialchars($row['event_type']) ?></span></td>
                                        <td><?= date('M d, Y', strtotime($row['event_date'])) ?></td>
                                        <td><?= htmlspecialchars($row['event_time']) ?></td>
                                        <td><?= htmlspecialchars($row['venue']) ?></td>
                                        <td><span class="status-tag <?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">No upcoming matches or schedules posted yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="schedule-section-card">
                <h2>Reserved Facility Availability</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Reserved Venue</th>
                                <th>Reserved Date</th>
                                <th>Time Slot</th>
                                <th>Reserved By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($upcoming_bookings && $upcoming_bookings->num_rows > 0): ?>
                                <?php while ($booking = $upcoming_bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($booking['facility_name']) ?></td>
                                        <td><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                                        <td><?= htmlspecialchars($booking['time_slot']) ?></td>
                                        <td><?= htmlspecialchars($booking['booked_by']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="no-data">No facilities currently reserved. All grounds available!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="schedule-section-card">
                <h2>Past Matches & Training Fixtures</h2>

                <form method="GET" action="schedules.php" class="filter-bar">
                    <select name="filter" id="filterSelect" onchange="toggleFilterInputs(this.value)">
                        <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>>All Past Matches</option>
                        <option value="month" <?= $filter_type === 'month' ? 'selected' : '' ?>>By Month</option>
                        <option value="week" <?= $filter_type === 'week' ? 'selected' : '' ?>>Past 7 Days</option>
                        <option value="date" <?= $filter_type === 'date' ? 'selected' : '' ?>>Specific Date</option>
                    </select>

                    <input type="month" name="month" id="monthInput" value="<?= htmlspecialchars($selected_month) ?>" style="display: <?= $filter_type === 'month' ? 'inline-block' : 'none' ?>;">
                    <input type="date" name="date" id="dateInput" value="<?= htmlspecialchars($selected_date) ?>" style="display: <?= $filter_type === 'date' ? 'inline-block' : 'none' ?>;">

                    <button type="submit" class="filter-btn"><i class='bx bx-filter-alt'></i> Filter</button>
                    <a href="schedules.php" class="filter-reset">Reset Filter</a>
                </form>

                <div class="table-responsive table-scroll">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Sport</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Venue</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($past_schedules && $past_schedules->num_rows > 0): ?>
                                <?php while ($row = $past_schedules->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['sport_type']) ?></td>
                                        <td><span class="type-pill"><?= htmlspecialchars($row['event_type']) ?></span></td>
                                        <td><?= date('M d, Y', strtotime($row['event_date'])) ?></td>
                                        <td><?= htmlspecialchars($row['event_time']) ?></td>
                                        <td><?= htmlspecialchars($row['venue']) ?></td>
                                        <td><span class="status-tag completed" style="background: rgba(100,100,100,0.2); color: #cbd5e1;">COMPLETED</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">No past matches found for the selected filter.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-about">
                <h3>NSBM Sports Club Circle</h3>
                <p>Your ultimate destination for campus athletics, club management, match schedules, and sports facility bookings.</p>
            </div>

            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="sports_clubs.php">Sports Clubs</a></li>
                    <li><a href="schedules.php">Schedules & Matches</a></li>
                    <li><a href="facility_booking.php">Facility Bookings</a></li>
                </ul>
            </div>

            <div class="footer-social">
                <h4>Follow Us</h4>
                <div class="social-icons">
                    <a href="https://www.facebook.com/nsbm.lk/" target="_blank" title="Facebook"><i class='bx bxl-facebook'></i></a>
                    <a href="https://www.instagram.com/nsbmgreenuniversity/?hl=en" target="_blank" title="Instagram"><i class='bx bxl-instagram'></i></a>
                    <a href="https://www.youtube.com/@NSBMGreenUniversityTown" target="_blank" title="YouTube"><i class='bx bxl-youtube'></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y'); ?> NSBM Sports Club Circle. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        function toggleFilterInputs(val) {
            document.getElementById('monthInput').style.display = (val === 'month') ? 'inline-block' : 'none';
            document.getElementById('dateInput').style.display = (val === 'date') ? 'inline-block' : 'none';
        }
    </script>
    <script src="script.js"></script>
<?php include 'chat_widget.php'; ?>
</body>
</html>