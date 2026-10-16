<?php
session_start();
if (!isset($_SESSION['user_email']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli("localhost", "root", "", "login_db");
if ($conn->connect_error) {
    die("Database connection failed.");
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT email, fullname, profile_photo FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

// Display helpers
$displayName = $user['fullname'] ?: $user['email'];
$photoUrl    = (!empty($user['profile_photo']) && is_file(__DIR__ . '/' . $user['profile_photo'])) ? $user['profile_photo'] : '';
$handle      = '@' . strtolower(preg_replace('/[^a-z0-9._]/i', '', explode('@', $user['email'])[0]));

$initials = '';
foreach (array_slice(preg_split('/\s+/', trim($displayName)), 0, 2) as $part) {
    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard - Student Information System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy:      #1f4e79;
            --blue:      #3b82c4;
            --sky:       #8ec5ee;
            --mist:      #e6f1fb;
            --page:      #eef5fc;
            --card:      #ffffff;
            --ink:       #17324d;
            --ink-soft:  #5f7a94;
            --line:      #d6e6f5;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Nunito', 'Segoe UI', system-ui, sans-serif;
            background: var(--page);
            color: var(--ink);
            margin: 0;
            min-height: 100vh;
        }

        /* ---------- Top navigation ---------- */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--line);
        }
        .topbar-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 12px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 18px;
            color: var(--navy);
        }
        .brand-mark {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--blue), var(--navy));
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .brand-mark svg { width: 20px; height: 20px; color: #fff; }

        .nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav a {
            text-decoration: none;
            font-weight: 700;
            font-size: 14.5px;
            color: var(--ink-soft);
            padding: 9px 16px;
            border-radius: 999px;
        }
        .nav a:hover { background: var(--mist); color: var(--navy); }
        .nav a.active { background: var(--mist); color: var(--navy); }
        .nav a.logout { background: var(--navy); color: #fff; }
        .nav a.logout:hover { background: var(--blue); }

        a:focus-visible {
            outline: 3px solid var(--navy);
            outline-offset: 2px;
        }

        /* ---------- Cover ---------- */
        .cover {
            height: 240px;
            background: #1f4e79;
        }

        .wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 28px 60px;
        }

        /* ---------- Profile header ---------- */
        .profile {
            display: flex;
            align-items: flex-end;
            gap: 30px;
            margin-top: -70px;
            flex-wrap: wrap;
        }
        .avatar-ring {
            width: 156px;
            height: 156px;
            flex: none;
            border-radius: 50%;
            padding: 5px;
            background: conic-gradient(var(--sky), var(--blue), var(--navy), var(--sky));
        }
        .avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 6px solid var(--page);
            background: var(--mist);
            color: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 52px;
            font-weight: 800;
            overflow: hidden;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .who { flex: 1; min-width: 260px; padding-bottom: 6px; }
        .name {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
            line-height: 1.15;
        }
        .handle {
            margin: 4px 0 0;
            color: var(--ink-soft);
            font-weight: 700;
            font-size: 16px;
        }
        .bio {
            margin: 10px 0 0;
            color: var(--ink-soft);
            font-size: 15px;
        }

        .stats {
            display: flex;
            gap: 36px;
            margin-top: 16px;
        }
        .stat strong {
            display: block;
            font-size: 20px;
            font-weight: 800;
        }
        .stat span {
            font-size: 13px;
            color: var(--ink-soft);
            font-weight: 700;
        }

        .actions {
            display: flex;
            gap: 12px;
            padding-bottom: 8px;
        }
        .btn {
            text-decoration: none;
            font-weight: 800;
            font-size: 15px;
            padding: 13px 26px;
            border-radius: 12px;
            transition: transform 0.12s, background-color 0.12s;
            white-space: nowrap;
        }
        .btn:active { transform: scale(0.97); }
        .btn-primary { background: var(--blue); color: #fff; }
        .btn-primary:hover { background: var(--navy); }
        .btn-secondary {
            background: var(--card);
            color: var(--navy);
            border: 1.5px solid var(--line);
        }
        .btn-secondary:hover { background: var(--mist); }

        /* ---------- Content grid ---------- */
        .grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 24px;
            margin-top: 36px;
            align-items: start;
        }

        .card {
            background: var(--card);
            padding: 26px 28px 10px;
            border-radius: 22px;
            border: 1px solid var(--line);
            box-shadow: 0 10px 28px rgba(31, 78, 121, 0.08);
        }
        .card h3 {
            margin: 0 0 6px;
            font-size: 19px;
            font-weight: 800;
            color: var(--navy);
        }
        .card .sub {
            margin: 0 0 14px;
            font-size: 14px;
            color: var(--ink-soft);
        }

        .row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 0;
            border-top: 1px solid var(--line);
        }
        .icon {
            width: 44px;
            height: 44px;
            flex: none;
            border-radius: 13px;
            background: var(--mist);
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon svg { width: 22px; height: 22px; }
        .row .label {
            font-size: 13px;
            color: var(--ink-soft);
            font-weight: 700;
        }
        .row .value {
            font-size: 16px;
            font-weight: 700;
            word-break: break-word;
        }
        .badge {
            margin-left: auto;
            background: #dff1e8;
            color: #1f7a4d;
            font-size: 12px;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 999px;
        }

        /* Quick action tiles (like TikTok's video grid) */
        .tiles {
            display: grid;
            gap: 18px;
        }
        .tile {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            min-height: 150px;
            padding: 22px;
            border-radius: 22px;
            text-decoration: none;
            color: #fff;
            position: relative;
            overflow: hidden;
            transition: transform 0.15s;
        }
        .tile:hover { transform: translateY(-3px); }
        .tile::after {
            content: "";
            position: absolute;
            right: -30px;
            top: -30px;
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.16);
        }
        .tile-a { background: linear-gradient(135deg, var(--blue), var(--navy)); }
        .tile-b { background: linear-gradient(135deg, #6fb3e6, var(--blue)); }
        .tile strong { font-size: 19px; font-weight: 800; position: relative; z-index: 1; }
        .tile span { font-size: 14px; opacity: 0.92; position: relative; z-index: 1; }

        /* ---------- Responsive ---------- */
        @media (max-width: 860px) {
            .grid { grid-template-columns: 1fr; }
            .profile { justify-content: center; text-align: center; }
            .who { text-align: center; }
            .stats { justify-content: center; }
            .actions { width: 100%; justify-content: center; }
        }
        @media (max-width: 520px) {
            .topbar-inner, .wrap { padding-left: 16px; padding-right: 16px; }
            .nav a:not(.logout) { display: none; }
            .cover { height: 180px; }
            .name { font-size: 26px; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <div class="brand">
            <div class="brand-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/></svg>
            </div>
            Student Portal
        </div>
        <nav class="nav">
            <a href="student_dashboard.php" class="active">Home</a>
            <a href="student_profile.php">My profile</a>
            <a href="student_edit.php">Edit info</a>
            <a href="index.php" class="logout">Log out</a>
        </nav>
    </div>
</header>

<div class="cover"></div>

<main class="wrap">

    <section class="profile">
        <div class="avatar-ring">
            <div class="avatar">
                <?php if ($photoUrl): ?>
                    <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Your profile photo">
                <?php else: ?>
                    <?php echo htmlspecialchars($initials ?: 'S'); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="who">
            <h1 class="name"><?php echo htmlspecialchars($displayName); ?></h1>
            <p class="handle"><?php echo htmlspecialchars($handle); ?></p>
            <p class="bio">Welcome back! What would you like to do today?</p>

            <div class="stats">
                <div class="stat"><strong>Student</strong><span>Role</span></div>
                <div class="stat"><strong>#<?php echo (int)$user_id; ?></strong><span>Student ID</span></div>
                <div class="stat"><strong>Active</strong><span>Status</span></div>
            </div>
        </div>

        <div class="actions">
            <a href="student_profile.php" class="btn btn-primary">View profile</a>
            <a href="student_edit.php" class="btn btn-secondary">Edit info</a>
        </div>
    </section>

    <div class="grid">

        <section class="card">
            <h3>My information</h3>
            <p class="sub">Here's what the school has on file for you.</p>

            <div class="row">
                <div class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                </div>
                <div>
                    <div class="label">Full name</div>
                    <div class="value"><?php echo htmlspecialchars($user['fullname'] ?: 'Not added yet'); ?></div>
                </div>
            </div>

            <div class="row">
                <div class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                </div>
                <div>
                    <div class="label">Email</div>
                    <div class="value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
            </div>

            <div class="row">
                <div class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/></svg>
                </div>
                <div>
                    <div class="label">Account type</div>
                    <div class="value">Student</div>
                </div>
                <span class="badge">Verified</span>
            </div>
        </section>

        <aside class="tiles">
            <a href="student_profile.php" class="tile tile-a">
                <strong>View my profile</strong>
                <span>See your full personal information</span>
            </a>
            <a href="student_edit.php" class="tile tile-b">
                <strong>Edit my information</strong>
                <span>Keep your details up to date</span>
            </a>
        </aside>

    </div>
</main>

</body>
</html>