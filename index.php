<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli("localhost", "root", "", "login_db");
if ($conn->connect_error) {
    die("Database connection failed.");
}

$login_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login_submit'])) {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user["password"])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit();
        } else {
            $login_message = "Incorrect email or password.";
        }
    } else {
        $login_message = "Incorrect email or password.";
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Student Information System</title>
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
            --danger-bg: #fdecec;
            --danger:    #b42318;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Nunito', 'Segoe UI', system-ui, sans-serif;
            color: var(--ink);
            margin: 0;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            background: var(--page);
        }

        a:focus-visible, button:focus-visible, input:focus-visible {
            outline: 3px solid var(--navy);
            outline-offset: 2px;
        }

        /* ---------- Left brand panel ---------- */
        .brand-panel {
            position: relative;
            overflow: hidden;
            padding: 48px 56px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background:
                radial-gradient(circle at 10% 110%, var(--sky) 0 26%, transparent 27%),
                radial-gradient(circle at 95% -5%, #5aa0d8 0 24%, transparent 25%),
                radial-gradient(circle at 70% 120%, rgba(255,255,255,0.16) 0 20%, transparent 21%),
                linear-gradient(130deg, var(--navy), var(--blue));
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 20px;
        }
        .brand-mark {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .brand-mark svg { width: 22px; height: 22px; }

        .pitch h2 {
            margin: 0 0 14px;
            font-size: 40px;
            line-height: 1.12;
            font-weight: 800;
            max-width: 460px;
        }
        .pitch p {
            margin: 0;
            font-size: 17px;
            line-height: 1.5;
            max-width: 420px;
            opacity: 0.92;
        }

        /* Mini profile preview */
        .preview {
            margin-top: 32px;
            display: flex;
            align-items: center;
            gap: 16px;
            max-width: 380px;
            padding: 16px 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(6px);
        }
        .preview-ring {
            width: 62px;
            height: 62px;
            flex: none;
            border-radius: 50%;
            padding: 3px;
            background: conic-gradient(#fff, var(--sky), #fff);
        }
        .preview-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: var(--mist);
            color: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
        }
        .preview strong { display: block; font-size: 16px; }
        .preview span { font-size: 13.5px; opacity: 0.85; }

        .foot { font-size: 13.5px; opacity: 0.8; }

        /* ---------- Right form panel ---------- */
        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 28px;
        }
        .form-box { width: 100%; max-width: 420px; }
        .form-box h1 {
            margin: 0 0 6px;
            font-size: 30px;
            font-weight: 800;
            color: var(--navy);
        }
        .lead { margin: 0 0 28px; color: var(--ink-soft); font-size: 15.5px; }

        .error {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid #f5c2c0;
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14.5px;
            margin-bottom: 20px;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 800;
            font-size: 14px;
            color: var(--navy);
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid var(--line);
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            color: var(--ink);
            background: #fff;
            transition: border-color 0.12s, box-shadow 0.12s;
        }
        .form-group input:hover { border-color: var(--sky); }
        .form-group input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(59, 130, 196, 0.15);
        }

        .btn {
            display: block;
            width: 100%;
            text-align: center;
            text-decoration: none;
            font-family: inherit;
            font-weight: 800;
            font-size: 16px;
            padding: 14px 18px;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.12s, background-color 0.12s;
        }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--blue); color: #fff; border: none; }
        .btn-primary:hover { background: var(--navy); }
        .btn-secondary {
            background: #fff;
            color: var(--navy);
            border: 1.5px solid var(--line);
        }
        .btn-secondary:hover { background: var(--mist); }

        .divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 26px 0 18px;
            color: var(--ink-soft);
            font-size: 14px;
            font-weight: 700;
        }
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        @media (max-width: 900px) {
            body { grid-template-columns: 1fr; }
            .brand-panel { padding: 28px 24px; gap: 24px; }
            .pitch h2 { font-size: 28px; }
            .preview, .foot { display: none; }
            .form-panel { padding: 32px 20px 48px; }
        }
    </style>
</head>
<body>

<section class="brand-panel">
    <div class="brand">
        <div class="brand-mark">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5"/></svg>
        </div>
        Student Information System
    </div>

    <div class="pitch">
        <h2>Your student profile, all in one place.</h2>
        <p>Keep your details up to date, add a profile photo and see your information anytime.</p>

       
    </div>

    <div class="foot">Student Portal</div>
</section>

<main class="form-panel">
    <div class="form-box">
        <h1>Welcome back</h1>
        <p class="lead">Log in to see your profile.</p>

        <?php if (!empty($login_message)): ?>
            <div class="error" role="alert"><?php echo htmlspecialchars($login_message); ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>

            <button type="submit" name="login_submit" class="btn btn-primary">Log in</button>
        </form>

        <div class="divider">New here?</div>
        <a href="register.php" class="btn btn-secondary">Create an account</a>
    </div>
</main>

</body>
</html>