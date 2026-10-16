<?php
session_start();
if (!isset($_SESSION['user_email']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli("localhost", "root", "", "login_db");
if ($conn->connect_error) {
    die("Database connection failed.");
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
    $token = $_POST['csrf'] ?? '';
    $delete_id = intval($_POST['delete_id']);

    if (hash_equals($_SESSION['csrf'], $token) && $delete_id != $_SESSION['user_id']) {
        // Remove the user's photo file too
        $p_stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
        $p_stmt->bind_param("i", $delete_id);
        $p_stmt->execute();
        $p_row = $p_stmt->get_result()->fetch_assoc();
        $p_stmt->close();
        if (!empty($p_row['profile_photo']) && is_file(__DIR__ . '/' . $p_row['profile_photo'])) {
            @unlink(__DIR__ . '/' . $p_row['profile_photo']);
        }

        $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $del_stmt->bind_param("i", $delete_id);
        $del_stmt->execute();
        $del_stmt->close();
        $_SESSION['flash'] = "User deleted.";
    }

    $back = "admin_dashboard.php";
    if (!empty($_POST['search'])) {
        $back .= "?search=" . urlencode($_POST['search']);
    }
    header("Location: " . $back);
    exit();
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Summary counts (always for all users)
$total = $students = $admins = 0;
$counts = $conn->query("SELECT role, COUNT(*) AS c FROM users GROUP BY role");
while ($c = $counts->fetch_assoc()) {
    $total += $c['c'];
    if ($c['role'] === 'student') $students = (int)$c['c'];
    if ($c['role'] === 'admin')   $admins   = (int)$c['c'];
}

// Search handling
$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email LIKE ? OR fullname LIKE ? ORDER BY id ASC");
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query("SELECT * FROM users ORDER BY id ASC");
}

// Check if a specific student profile is requested for viewing
$view_user = null;
if (isset($_GET['view_id'])) {
    $view_id = intval($_GET['view_id']);
    $v_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $v_stmt->bind_param("i", $view_id);
    $v_stmt->execute();
    $view_user = $v_stmt->get_result()->fetch_assoc();
    $v_stmt->close();
}
$conn->close();

// ---------- Helpers ----------
function initials_of($name) {
    $out = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $part) {
        $out .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $out ?: '?';
}
function photo_of($row) {
    $p = $row['profile_photo'] ?? '';
    return (!empty($p) && is_file(__DIR__ . '/' . $p)) ? $p : '';
}
function val($row, $key) {
    return !empty($row[$key]) ? htmlspecialchars($row[$key]) : '<span class="empty">Not provided</span>';
}

$closeUrl = 'admin_dashboard.php' . (!empty($search) ? '?search=' . urlencode($search) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Student Information System</title>
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
            --danger:    #b42318;
            --danger-bg: #fdecec;
            --ok:        #1f7a4d;
            --ok-bg:     #dff1e8;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Nunito', 'Segoe UI', system-ui, sans-serif;
            background: var(--page);
            color: var(--ink);
            margin: 0;
            min-height: 100vh;
        }

        a:focus-visible, button:focus-visible, input:focus-visible {
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
        .nav a.active { background: var(--mist); color: var(--navy); }
        .nav a.logout { background: var(--navy); color: #fff; }
        .nav a.logout:hover { background: var(--blue); }

        /* ---------- Hero ---------- */
        .hero {
            height: 220px;
            color: #fff;
            background: #1f4e79;
                
        }
        .hero-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 38px 28px 0;
        }
        .hero h1 { margin: 0 0 6px; font-size: 32px; font-weight: 800; }
        .hero p { margin: 0; font-size: 16px; opacity: 0.92; }

        .wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 28px 60px;
        }

        /* ---------- Stat cards ---------- */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: -60px;
        }
        .stat {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 10px 28px rgba(31, 78, 121, 0.08);
        }
        .stat .icon {
            width: 48px;
            height: 48px;
            flex: none;
            border-radius: 14px;
            background: var(--mist);
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat .icon svg { width: 24px; height: 24px; }
        .stat strong { display: block; font-size: 28px; font-weight: 800; line-height: 1.1; }
        .stat span { font-size: 14px; color: var(--ink-soft); font-weight: 700; }

        /* ---------- Directory card ---------- */
        .panel {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 10px 28px rgba(31, 78, 121, 0.08);
            margin-top: 28px;
            padding: 26px 28px 12px;
        }
        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .panel-head h2 { margin: 0; font-size: 20px; font-weight: 800; color: var(--navy); }
        .panel-head p { margin: 4px 0 0; font-size: 14px; color: var(--ink-soft); }

        .search-box { display: flex; gap: 10px; flex: 1; min-width: 260px; max-width: 520px; }
        .search-field { position: relative; flex: 1; }
        .search-field svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--ink-soft);
        }
        .search-field input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid var(--line);
            border-radius: 12px;
            font-family: inherit;
            font-size: 14.5px;
            color: var(--ink);
            background: #f8fbff;
        }
        .search-field input:focus {
            outline: none;
            border-color: var(--blue);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 196, 0.15);
        }
        .btn {
            font-family: inherit;
            font-weight: 800;
            font-size: 14.5px;
            padding: 12px 20px;
            border-radius: 12px;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            transition: background-color 0.12s, transform 0.12s;
        }
        .btn:active { transform: scale(0.97); }
        .btn-primary { background: var(--blue); color: #fff; border: none; }
        .btn-primary:hover { background: var(--navy); }
        .btn-secondary { background: #fff; color: var(--navy); border: 1.5px solid var(--line); }
        .btn-secondary:hover { background: var(--mist); }

        .flash {
            background: var(--ok-bg);
            color: var(--ok);
            border: 1px solid #bfe3cf;
            border-radius: 12px;
            padding: 11px 16px;
            font-weight: 700;
            font-size: 14.5px;
            margin-bottom: 16px;
        }

        /* Table */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 640px; }
        th {
            text-align: left;
            padding: 12px 14px;
            font-size: 13px;
            font-weight: 800;
            color: var(--ink-soft);
            background: var(--mist);
        }
        th:first-child { border-radius: 12px 0 0 12px; }
        th:last-child  { border-radius: 0 12px 12px 0; }
        td {
            padding: 14px;
            border-bottom: 1px solid var(--line);
            font-size: 14.5px;
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fbff; }

        .person { display: flex; align-items: center; gap: 12px; }
        .mini-avatar {
            width: 42px;
            height: 42px;
            flex: none;
            border-radius: 50%;
            background: var(--mist);
            color: var(--navy);
            font-weight: 800;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px var(--sky);
        }
        .mini-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .person strong { display: block; font-weight: 800; }
        .person span { font-size: 13px; color: var(--ink-soft); }
        .id-cell { color: var(--ink-soft); font-weight: 700; }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 800;
        }
        .badge-admin   { background: #dfe9fb; color: var(--navy); }
        .badge-student { background: var(--ok-bg); color: var(--ok); }

        .row-actions { display: flex; align-items: center; gap: 8px; }
        .chip {
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            padding: 7px 14px;
            border-radius: 999px;
            text-decoration: none;
            cursor: pointer;
            border: 1.5px solid var(--line);
            background: #fff;
            color: var(--navy);
        }
        .chip:hover { background: var(--mist); }
        .chip-danger { color: var(--danger); border-color: #f2c7c4; }
        .chip-danger:hover { background: var(--danger-bg); }
        .row-actions form { margin: 0; }

        .empty-row { text-align: center; padding: 36px 14px !important; color: var(--ink-soft); font-weight: 700; }
        .empty { color: #9db3c8; font-weight: 600; font-style: italic; }

        /* ---------- Student details modal ---------- */
        .overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            background: rgba(23, 50, 77, 0.55);
            backdrop-filter: blur(3px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .overlay-bg { position: absolute; inset: 0; }
        .modal {
            position: relative;
            width: 100%;
            max-width: 680px;
            max-height: 90vh;
            overflow-y: auto;
            background: var(--card);
            border-radius: 26px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.3);
        }
        .modal-cover {
            height: 110px;
            background:
                radial-gradient(circle at 12% 130%, var(--sky) 0 30%, transparent 31%),
                linear-gradient(120deg, var(--navy), var(--blue));
        }
        .close-x {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.5);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .close-x:hover { background: rgba(255, 255, 255, 0.4); }
        .close-x svg { width: 18px; height: 18px; }

        .modal-body { padding: 0 30px 28px; }
        .modal-head {
            display: flex;
            align-items: flex-end;
            gap: 18px;
            margin-top: -44px;
            flex-wrap: wrap;
        }
        .big-ring {
            width: 104px;
            height: 104px;
            flex: none;
            border-radius: 50%;
            padding: 4px;
            background: conic-gradient(var(--sky), var(--blue), var(--navy), var(--sky));
        }
        .big-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 5px solid var(--card);
            background: var(--mist);
            color: var(--navy);
            font-size: 34px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .big-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .modal-head h3 { margin: 0; font-size: 22px; font-weight: 800; }
        .modal-head p { margin: 2px 0 0; color: var(--ink-soft); font-size: 14.5px; font-weight: 700; word-break: break-all; }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 24px;
            margin-top: 20px;
        }
        .detail { padding: 12px 0; border-top: 1px solid var(--line); }
        .detail .label { font-size: 12.5px; color: var(--ink-soft); font-weight: 700; }
        .detail .value { margin-top: 2px; font-size: 15.5px; font-weight: 700; word-break: break-word; }
        .modal-foot { margin-top: 18px; display: flex; justify-content: flex-end; }

        /* ---------- Responsive ---------- */
        @media (max-width: 780px) {
            .stats { grid-template-columns: 1fr; margin-top: -50px; }
            .hero { height: 200px; }
            .detail-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 520px) {
            .topbar-inner, .wrap, .hero-inner { padding-left: 16px; padding-right: 16px; }
            .hero h1 { font-size: 26px; }
            .panel { padding: 20px 16px 8px; }
            .modal-body { padding: 0 20px 24px; }
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
            Admin Portal
        </div>
        <nav class="nav">
            <a href="admin_dashboard.php" class="active">Dashboard</a>
            <a href="index.php" class="logout">Log out</a>
        </nav>
    </div>
</header>

<section class="hero">
    <div class="hero-inner">
        <h1>Admin dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
    </div>
</section>

<main class="wrap">

    <div class="stats">
        <div class="stat">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.5 3-6 6.5-6s6.5 2.5 6.5 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18 14.5c2 .7 3.5 2.6 3.5 5.5"/></svg>
            </div>
            <div><strong><?php echo $total; ?></strong><span>Total users</span></div>
        </div>
        <div class="stat">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/></svg>
            </div>
            <div><strong><?php echo $students; ?></strong><span>Students</span></div>
        </div>
        <div class="stat">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 4.5 3.2 7.8 8 9 4.8-1.2 8-4.5 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <div><strong><?php echo $admins; ?></strong><span>Admins</span></div>
        </div>
    </div>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Student directory</h2>
                <p>Search, view details or remove users.</p>
            </div>

            <form action="admin_dashboard.php" method="GET" class="search-box">
                <div class="search-field">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="text" name="search" placeholder="Search by email or full name" aria-label="Search users" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($flash): ?>
            <div class="flash" role="status"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php $photo = photo_of($row); $name = $row['fullname'] ?: $row['email']; ?>
                            <tr>
                                <td class="id-cell">#<?php echo (int)$row['id']; ?></td>
                                <td>
                                    <div class="person">
                                        <div class="mini-avatar">
                                            <?php if ($photo): ?>
                                                <img src="<?php echo htmlspecialchars($photo); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo htmlspecialchars(initials_of($name)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($row['fullname'] ?: 'No name yet'); ?></strong>
                                            <span><?php echo htmlspecialchars($row['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($row['role'] === 'admin'): ?>
                                        <span class="badge badge-admin">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-student">Student</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <?php if ($row['role'] === 'student'): ?>
                                            <a class="chip" href="admin_dashboard.php?view_id=<?php echo (int)$row['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">View info</a>
                                        <?php endif; ?>

                                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" action="admin_dashboard.php" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                                <input type="hidden" name="delete_id" value="<?php echo (int)$row['id']; ?>">
                                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                                <button type="submit" class="chip chip-danger">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="empty-row">No users found. Try a different search.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php if ($view_user): ?>
    <?php
        $vPhoto = photo_of($view_user);
        $vName  = $view_user['fullname'] ?: $view_user['email'];
    ?>
    <div class="overlay" role="dialog" aria-modal="true" aria-label="Student details">
        <a class="overlay-bg" href="<?php echo htmlspecialchars($closeUrl); ?>" aria-label="Close details"></a>
        <div class="modal">
            <div class="modal-cover"></div>
            <a href="<?php echo htmlspecialchars($closeUrl); ?>" class="close-x" aria-label="Close details">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </a>

            <div class="modal-body">
                <div class="modal-head">
                    <div class="big-ring">
                        <div class="big-avatar">
                            <?php if ($vPhoto): ?>
                                <img src="<?php echo htmlspecialchars($vPhoto); ?>" alt="Profile photo of <?php echo htmlspecialchars($vName); ?>">
                            <?php else: ?>
                                <?php echo htmlspecialchars(initials_of($vName)); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <h3><?php echo htmlspecialchars($vName); ?></h3>
                        <p><?php echo htmlspecialchars($view_user['email']); ?></p>
                    </div>
                </div>

                <div class="detail-grid">
                    <div class="detail"><div class="label">Full name</div><div class="value"><?php echo val($view_user, 'fullname'); ?></div></div>
                    <div class="detail"><div class="label">Nickname</div><div class="value"><?php echo val($view_user, 'nickname'); ?></div></div>
                    <div class="detail"><div class="label">Address or city</div><div class="value"><?php echo val($view_user, 'address'); ?></div></div>
                    <div class="detail"><div class="label">Favorite subject</div><div class="value"><?php echo val($view_user, 'favorite_subject'); ?></div></div>
                    <div class="detail"><div class="label">2nd preferred course</div><div class="value"><?php echo val($view_user, 'preferred_course_2'); ?></div></div>
                    <div class="detail"><div class="label">2nd preferred university</div><div class="value"><?php echo val($view_user, 'preferred_univ_2'); ?></div></div>
                    <div class="detail"><div class="label">Dream job</div><div class="value"><?php echo val($view_user, 'dream_job'); ?></div></div>
                    <div class="detail"><div class="label">Most afraid of</div><div class="value"><?php echo val($view_user, 'greatest_fear'); ?></div></div>
                    <div class="detail" style="grid-column: 1 / -1;"><div class="label">Least favorite subject or activity</div><div class="value"><?php echo val($view_user, 'least_favorite_subject'); ?></div></div>
                </div>

                <div class="modal-foot">
                    <a href="<?php echo htmlspecialchars($closeUrl); ?>" class="btn btn-primary">Close</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

</body>
</html>