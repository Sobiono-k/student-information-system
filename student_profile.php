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

// Fetch current user full profile details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

// Display helpers
$displayName = $user['fullname'] ?: $user['email'];
$handle      = '@' . strtolower(preg_replace('/[^a-z0-9._]/i', '', explode('@', $user['email'])[0]));
$photo       = $user['profile_photo'] ?? '';
$photoUrl    = (!empty($photo) && is_file(__DIR__ . '/' . $photo)) ? $photo : '';

$initials = '';
foreach (array_slice(preg_split('/\s+/', trim($displayName)), 0, 2) as $part) {
    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
}

// Show a value, or a soft placeholder if it's empty
function show($user, $key) {
    return !empty($user[$key])
        ? htmlspecialchars($user[$key])
        : '<span class="empty">Not provided</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile Info</title>
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

        a:focus-visible {
            outline: 3px solid var(--navy);
            outline-offset: 2px;
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
        .nav { display: flex; align-items: center; gap: 8px; }
        .nav a {
            text-decoration: none;
            font-weight: 700;
            font-size: 14.5px;
            color: var(--ink-soft);
            padding: 9px 16px;
            border-radius: 999px;
        }
        .nav a:hover, .nav a.active { background: var(--mist); color: var(--navy); }
        .nav a.logout { background: var(--navy); color: #fff; }
        .nav a.logout:hover { background: var(--blue); }

        /* ---------- Cover ---------- */
        .cover {
            height: 240px;
            background:
                radial-gradient(circle at 12% 130%, var(--sky) 0 30%, transparent 31%),
                radial-gradient(circle at 88% -20%, #5aa0d8 0 28%, transparent 29%),
                radial-gradient(circle at 60% 140%, rgba(255,255,255,0.18) 0 22%, transparent 23%),
                linear-gradient(120deg, var(--navy), var(--blue));
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
        .name { margin: 0; font-size: 32px; font-weight: 800; line-height: 1.15; }
        .handle { margin: 4px 0 0; color: var(--ink-soft); font-weight: 700; font-size: 16px; }
        .bio { margin: 10px 0 0; color: var(--ink-soft); font-size: 15px; }

        .actions { display: flex; gap: 12px; padding-bottom: 8px; }
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
        .btn-secondary { background: var(--card); color: var(--navy); border: 1.5px solid var(--line); }
        .btn-secondary:hover { background: var(--mist); }

        /* ---------- Info cards ---------- */
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 36px;
            align-items: start;
        }
        .card {
            background: var(--card);
            padding: 24px 26px 8px;
            border-radius: 22px;
            border: 1px solid var(--line);
            box-shadow: 0 10px 28px rgba(31, 78, 121, 0.08);
        }
        .card-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        .icon {
            width: 42px;
            height: 42px;
            flex: none;
            border-radius: 12px;
            background: var(--mist);
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon svg { width: 21px; height: 21px; }
        .card h3 { margin: 0; font-size: 18px; font-weight: 800; color: var(--navy); }

        .item { padding: 14px 0; border-top: 1px solid var(--line); }
        .item:first-of-type { margin-top: 6px; }
        .item .label { font-size: 13px; color: var(--ink-soft); font-weight: 700; }
        .item .value { margin-top: 3px; font-size: 16px; font-weight: 700; word-break: break-word; }
        .empty { color: #9db3c8; font-weight: 600; font-style: italic; }

        /* ---------- Responsive ---------- */
        @media (max-width: 1000px) {
            .grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 860px) {
            .profile { justify-content: center; text-align: center; }
            .who { text-align: center; }
            .actions { width: 100%; justify-content: center; }
        }
        @media (max-width: 660px) {
            .grid { grid-template-columns: 1fr; }
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
            <a href="student_dashboard.php">Home</a>
            <a href="student_profile.php" class="active">My profile</a>
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
            <p class="bio">
                <?php echo !empty($user['nickname'])
                    ? 'Also known as ' . htmlspecialchars($user['nickname'])
                    : 'This is your personal information profile'; ?>
            </p>
        </div>

        <div class="actions">
            <a href="student_edit.php" class="btn btn-primary">Edit information</a>
            <a href="student_dashboard.php" class="btn btn-secondary">Back to dashboard</a>
        </div>
    </section>

    <div class="grid">

        <section class="card">
            <div class="card-head">
                <div class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                </div>
                <h3>About me</h3>
            </div>
            <div class="item">
                <div class="label">Account email</div>
                <div class="value"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
            <div class="item">
                <div class="label">Full name</div>
                <div class="value"><?php echo show($user, 'fullname'); ?></div>
            </div>
            <div class="item">
                <div class="label">Nickname</div>
                <div class="value"><?php echo show($user, 'nickname'); ?></div>
            </div>
            <div class="item">
                <div class="label">Address or city</div>
                <div class="value"><?php echo show($user, 'address'); ?></div>
            </div>
        </section>

        <section class="card">
            <div class="card-head">
                <div class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/></svg>
                </div>
                <h3>School plans</h3>
            </div>
            <div class="item">
                <div class="label">2nd preferred course</div>
                <div class="value"><?php echo show($user, 'preferred_course_2'); ?></div>
            </div>
            <div class="item">
                <div class="label">2nd preferred university</div>
                <div class="value"><?php echo show($user, 'preferred_univ_2'); ?></div>
            </div>
            <div class="item">
                <div class="label">Favorite subject</div>
                <div class="value"><?php echo show($user, 'favorite_subject'); ?></div>
            </div>
            <div class="item">
                <div class="label">Dream job in the future</div>
                <div class="value"><?php echo show($user, 'dream_job'); ?></div>
            </div>
        </section>

        <section class="card">
            <div class="card-head">
                <div class="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10Z"/></svg>
                </div>
                <h3>A little more about me</h3>
            </div>
            <div class="item">
                <div class="label">Most afraid of</div>
                <div class="value"><?php echo show($user, 'greatest_fear'); ?></div>
            </div>
            <div class="item">
                <div class="label">Least favorite subject or activity</div>
                <div class="value"><?php echo show($user, 'least_favorite_subject'); ?></div>
            </div>
        </section>

    </div>
</main>

</body>
</html>