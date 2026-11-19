<?php

session_start();

$user_id = $_SESSION['user_id'] ?? null;
$name = $_SESSION['name'] ?? null;
$email = $_SESSION['email'] ?? null;
$role = $_SESSION['role'] ?? 'student';

$alerts = $_SESSION['alerts'] ?? [];
$active_form = $_SESSION['active_form'] ?? '';

unset($_SESSION['alerts']);
unset($_SESSION['active_form']);

?>
<!DOCTYPE html>
<html lang="en">   
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NSBM SportsHub</title>
    <link rel="stylesheet" href="style.css?v=9.9">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <style>
        .cards-section {
            padding: 60px 20px;
            display: flex;
            justify-content: center;
        }
        .flip-card-container {
            display: flex;
            gap: 26px;
            justify-content: center;
            flex-wrap: wrap;
            max-width: 1100px;
            width: 100%;
            perspective: 1400px;
        }
        .flip-card {
            width: 325px;
            height: 350px;
            background: transparent;
            cursor: pointer;
            perspective: 1200px;
            --spot-x: 50%;
            --spot-y: 50%;
            --spot-opacity: 0;
            --border-opacity: 0;
        }
        .flip-card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 1.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s ease;
            transform-style: preserve-3d;
            border-radius: 24px;
            box-shadow: 0 20px 44px -16px rgba(0, 0, 0, 0.65);
        }
        .flip-card:hover .flip-card-inner {
            transform: rotateY(180deg);
        }

        .flip-card-front, .flip-card-back {
            position: absolute;
            inset: 0;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 28px 24px;
            box-sizing: border-box;
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow: hidden;
            isolation: isolate;
        }

        .flip-card-front::before, .flip-card-back::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(
                240px circle at var(--spot-x) var(--spot-y),
                rgba(255, 255, 255, 0.16),
                transparent 70%
            );
            opacity: var(--spot-opacity);
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 2;
        }

        .flip-card-front::after, .flip-card-back::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1.5px;
            background: radial-gradient(
                220px circle at var(--spot-x) var(--spot-y),
                rgba(255, 255, 255, 0.65),
                transparent 70%
            );
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: var(--border-opacity);
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 3;
        }

        .flip-card-container .flip-card:nth-child(1) .flip-card-front,
        .flip-card-container .flip-card:nth-child(1) .flip-card-back {
            background: linear-gradient(rgba(15, 23, 42, 0.65), rgba(15, 23, 42, 0.78)), url('hcard1.png') no-repeat center center / cover;
        }
        .flip-card-container .flip-card:nth-child(2) .flip-card-front,
        .flip-card-container .flip-card:nth-child(2) .flip-card-back {
            background: linear-gradient(rgba(15, 23, 42, 0.65), rgba(15, 23, 42, 0.78)), url('hcard2.png') no-repeat center center / cover;
        }
        .flip-card-container .flip-card:nth-child(3) .flip-card-front,
        .flip-card-container .flip-card:nth-child(3) .flip-card-back {
            background: linear-gradient(rgba(15, 23, 42, 0.65), rgba(15, 23, 42, 0.78)), url('hcard3.png') no-repeat center center / cover;
        }

        .flip-card-back {
            transform: rotateY(180deg);
            text-decoration: none;
        }

        .flip-card-front h3, .flip-card-back h3 {
            color: #ffffff !important;
            font-size: 21px;
            margin-top: auto;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.55);
            position: relative;
            z-index: 4;
        }
        .flip-card-front p, .flip-card-back p {
            font-size: 13.5px;
            color: #e4e4e7 !important;
            line-height: 1.55;
            margin-bottom: 16px;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.55);
            position: relative;
            z-index: 4;
        }
        .flip-hint {
            font-size: 11.5px;
            color: #0abf30;
            font-weight: 600;
            margin-top: auto;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            position: relative;
            z-index: 4;
        }
        .flip-action-text {
            font-size: 13.5px;
            color: #4ade80;
            font-weight: 600;
            margin-top: auto;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 16px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            position: relative;
            z-index: 4;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="logo">
            <img src="logo2.png" alt="Logo">
        </a>
        <nav>
            <a href="index.php" style="color: #0abf30;">Home</a>
            <a href="sports_clubs.php">Sports Clubs</a>
            <a href="schedules.php">Schedules & Matches</a>
            <a href="facility_booking.php">Facility Bookings</a>
        </nav>
        <div class="user-auth">
            <?php if (!empty($name)): ?>
            <div class="profile-box">
                <div class="avatar-circle"><?= strtoupper($name[0]) ?></div>
                <div class="dropdown">
                    <a href="profile.php">My account</a>
                    <?php if ($role === 'admin'): ?>
                        <a href="admin.php">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
            <?php else: ?>
            <button type="button" class="login-btn-modal">Login</button>
            <?php endif; ?>
        </div>
    </header>
    
    <section class="hero-section">
        <h1>NSBM University Sports<br>Clubs Circle</h1>
        <p>Join sports clubs, keep track of match schedules, participate in tournaments, and reserve campus sports facilities.</p>
        
        <div class="hero-buttons">
            <a href="sports_clubs.php" class="hero-btn primary"><i class='bx bx-group'></i> Browse Clubs</a>
            <a href="schedules.php" class="hero-btn secondary"><i class='bx bx-calendar'></i> View Fixtures</a>
        </div>
    </section>

    <section class="cards-section">
        <div class="flip-card-container">
            
            <div class="flip-card spotlight-target" onclick="window.location.href='sports_clubs.php'">
                <div class="flip-card-inner">
                    <div class="flip-card-front">
                        <h3>Club Management</h3>
                        <p>Explore active sports teams, apply for memberships, and get official approvals.</p>
                        <span class="flip-hint">Hover to Flip ↻</span>
                    </div>
                    <div class="flip-card-back">
                        <h3>Club Memberships</h3>
                        <p>Connect with team leaders, track active rosters, and manage official approvals instantly.</p>
                        <span class="flip-action-text">Browse Clubs →</span>
                    </div>
                </div>
            </div>

            <div class="flip-card spotlight-target" onclick="window.location.href='schedules.php'">
                <div class="flip-card-inner">
                    <div class="flip-card-front">
                        <h3>Schedules & Matches</h3>
                        <p>Stay up to date with training practice sessions and competitive fixtures.</p>
                        <span class="flip-hint">Hover to Flip ↻</span>
                    </div>
                    <div class="flip-card-back">
                        <h3>Live Fixtures</h3>
                        <p>Never miss a game! View real-time training schedules, tournament dates, and match opponents.</p>
                        <span class="flip-action-text" style="color: #60a5fa;">View Fixtures →</span>
                    </div>
                </div>
            </div>

            <div class="flip-card spotlight-target" onclick="window.location.href='facility_booking.php'">
                <div class="flip-card-inner">
                    <div class="flip-card-front">
                        <h3>Facility Bookings</h3>
                        <p>Reserve grounds, indoor courts, and equipment directly online.</p>
                        <span class="flip-hint">Hover to Flip ↻</span>
                    </div>
                    <div class="flip-card-back">
                        <h3>Court Reservations</h3>
                        <p>Book indoor badminton courts, cricket nets, and sports gear online with instant confirmation.</p>
                        <span class="flip-action-text" style="color: #facc15;">Book Now →</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php if (!empty($alerts)): ?>
    <div class="alert-box">
        <?php foreach($alerts as $alert): ?>
        <div class="alert <?= $alert['type'] ?>">
            <i class='bx <?= $alert['type'] === 'success' ? 'bxs-check-circle' : 'bxs-x-circle'; ?>'></i>
            <span><?= htmlspecialchars($alert['message']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="auth-modal <?= $active_form === 'register' ? 'show slide' : ($active_form === 'login' ? 'show' : ''); ?>">
        <button type="button" class="close-btn-modal"><i class="bx bx-x-circle"></i></button> 
        <div class="form-box login">
            <h2>Login</h2>
            <form action="auth_process.php" method="POST">
                <div class="input-box">
                    <input type="email" name="email" placeholder="name@students.nsbm.ac.lk" required>
                    <i class="bx bx-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="bx bx-lock-alt"></i>
                </div>
                <button type="submit" name="login_btn" class="btn">Login</button>
                <p>Don't have an account? <a href="#" class="register-link">Register</a></p>
            </form>
        </div>

        <div class="form-box register">
            <h2>Register</h2>
            <form action="auth_process.php" method="POST">
                <div class="input-box">
                    <input type="text" name="name" placeholder="Name" required>
                    <i class="bx bx-user"></i>
                </div> 
                <div class="input-box">
                    <input type="email" name="email" placeholder="name@students.nsbm.ac.lk" required>
                    <i class="bx bx-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="bx bx-lock-alt"></i>
                </div>
                <button type="submit" name="register_btn" class="btn">Register</button>
                <p>Already have an account? <a href="#" class="login-link">Login</a></p>
            </form>
        </div>
    </div>

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
        (function() {
            const cards = document.querySelectorAll('.spotlight-target');
            const proximity = 80;

            window.addEventListener('pointermove', (e) => {
                cards.forEach((card) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    const isHovered = card.matches(':hover');
                    const mappedX = isHovered ? (rect.width - x) : x;

                    card.style.setProperty('--spot-x', `${mappedX}px`);
                    card.style.setProperty('--spot-y', `${y}px`);

                    const insideX = e.clientX >= rect.left && e.clientX <= rect.right;
                    const insideY = e.clientY >= rect.top && e.clientY <= rect.bottom;

                    if (insideX && insideY) {
                        card.style.setProperty('--spot-opacity', '1');
                        card.style.setProperty('--border-opacity', '1');
                    } else {
                        const dx = Math.max(rect.left - e.clientX, 0, e.clientX - rect.right);
                        const dy = Math.max(rect.top - e.clientY, 0, e.clientY - rect.bottom);
                        const dist = Math.hypot(dx, dy);

                        card.style.setProperty('--spot-opacity', '0');
                        if (dist <= proximity) {
                            const fade = 1 - (dist / proximity);
                            card.style.setProperty('--border-opacity', fade.toFixed(2));
                        } else {
                            card.style.setProperty('--border-opacity', '0');
                        }
                    }
                });
            });
        })();
    </script>

    <script src="script.js"></script>
    <?php include 'chat_widget.php'; ?>
</body>
</html>