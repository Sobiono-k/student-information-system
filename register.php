<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli("localhost", "root", "", "login_db");
if ($conn->connect_error) {
    die("Database connection failed.");
}

$register_message = "";
$message_type     = "info"; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "student";

    if (empty($email) || empty($password)) {
        $register_message = "Please fill in all fields.";
        $message_type = "error";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $hashed_password, $role);
            $stmt->execute();

            $register_message = "Registration successful! <a href='index.php'>Log in here</a>";
            $message_type = "success";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $register_message = "This email is already registered. <a href='index.php'>Log in here</a>";
                $message_type = "error";
            } else {
                $register_message = "Registration failed. Please try again.";
                $message_type = "error";
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Student Information System</title>
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
            --ok-bg:     #dff1e8;
            --ok:        #1f7a4d;
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

        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
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

        .steps {
            list-style: none;
            margin: 28px 0 0;
            padding: 0;
            display: grid;
            gap: 12px;
            max-width: 380px;
        }
        .steps li {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 16px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(6px);
            font-weight: 700;
            font-size: 15px;
        }
        .steps svg { width: 20px; height: 20px; flex: none; }

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

        .message {
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14.5px;
            margin-bottom: 20px;
            border: 1px solid var(--line);
            background: var(--mist);
            color: var(--navy);
        }
        .message.success { background: var(--ok-bg); color: var(--ok); border-color: #bfe3cf; }
        .message.error   { background: var(--danger-bg); color: var(--danger); border-color: #f5c2c0; }
        .message a { color: inherit; font-weight: 800; }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 800;
            font-size: 14px;
            color: var(--navy);
        }
        .form-group input, .form-group select {
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
        .form-group input:hover, .form-group select:hover { border-color: var(--sky); }
        .form-group input:focus, .form-group select:focus {
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
        .btn-secondary { background: #fff; color: var(--navy); border: 1.5px solid var(--line); }
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
            .steps, .foot { display: none; }
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
        <h2>Create your account and start your profile.</h2>
        <p>It only takes a minute. After you log in, you can add your details and a photo.</p>

        <ul class="steps" aria-hidden="true">
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                Register with your email
            </li>
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Fill in your information
            </li>
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l2-3h6l2 3h3v11H4V8Z"/><circle cx="12" cy="13" r="3.5"/></svg>
                Add your profile photo
            </li>
        </ul>
    </div>

    <div class="foot">Student Portal</div>
</section>

<main class="form-panel">
    <div class="form-box">
        <h1>Create an account</h1>
        <p class="lead">Fill in the details below to register.</p>

        <?php if (!empty($register_message)): ?>
            <?php /* Message text is set in this file only (never from user input), so the login link can be output as HTML. */ ?>
            <div class="message <?php echo $message_type; ?>" role="alert"><?php echo $register_message; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="new-password" required>
            </div>

            <div class="form-group">
                <label for="role">Register as</label>
                <select id="role" name="role">
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <div class="divider">Already registered?</div>
        <a href="index.php" class="btn btn-secondary">Back to login</a>
    </div>
</main>

</body>
</html>