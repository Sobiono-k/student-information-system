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
$error   = '';

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$currentPhoto = $user['profile_photo'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname               = trim($_POST['fullname'] ?? '');
    $nickname               = trim($_POST['nickname'] ?? '');
    $address                = trim($_POST['address'] ?? '');
    $preferred_course_2     = trim($_POST['preferred_course_2'] ?? '');
    $preferred_univ_2       = trim($_POST['preferred_univ_2'] ?? '');
    $favorite_subject       = trim($_POST['favorite_subject'] ?? '');
    $dream_job              = trim($_POST['dream_job'] ?? '');
    $greatest_fear          = trim($_POST['greatest_fear'] ?? '');
    $least_favorite_subject = trim($_POST['least_favorite_subject'] ?? '');

    $newPhoto = $currentPhoto;
    $uploadDir = __DIR__ . '/uploads/profile/';

    if (!empty($_POST['remove_photo'])) {
        if ($currentPhoto && is_file(__DIR__ . '/' . $currentPhoto)) {
            @unlink(__DIR__ . '/' . $currentPhoto);
        }
        $newPhoto = '';
    }

    // --- New photo upload ---
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['profile_photo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "The photo could not be uploaded. Please try again.";
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $error = "Your photo is too big. Please choose one under 2 MB.";
        } else {
            $info    = @getimagesize($file['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if ($info === false || !isset($allowed[$info['mime']])) {
                $error = "Please upload a JPG, PNG or WebP image.";
            } else {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = 'user_' . $user_id . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$info['mime']];

                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    // delete the previous photo
                    if ($currentPhoto && is_file(__DIR__ . '/' . $currentPhoto)) {
                        @unlink(__DIR__ . '/' . $currentPhoto);
                    }
                    $newPhoto = 'uploads/profile/' . $filename;
                } else {
                    $error = "Could not save your photo. Check that the uploads folder is writable.";
                }
            }
        }
    }

    if ($error === '') {
        $update_stmt = $conn->prepare("UPDATE users SET fullname=?, nickname=?, address=?, preferred_course_2=?, preferred_univ_2=?, favorite_subject=?, dream_job=?, greatest_fear=?, least_favorite_subject=?, profile_photo=? WHERE id=?");
        $update_stmt->bind_param("ssssssssssi", $fullname, $nickname, $address, $preferred_course_2, $preferred_univ_2, $favorite_subject, $dream_job, $greatest_fear, $least_favorite_subject, $newPhoto, $user_id);

        if ($update_stmt->execute()) {
            $update_stmt->close();
            $conn->close();
            header("Location: student_profile.php");
            exit();
        }
        $update_stmt->close();
        $error = "Your changes could not be saved. Please try again.";
    }

    // Keep what the student typed if there was an error
    $user = array_merge($user, $_POST);
    $user['profile_photo'] = $currentPhoto;
}

$conn->close();

// Helpers for the page
$displayName = $user['fullname'] ?: $user['email'];
$initials = '';
foreach (array_slice(preg_split('/\s+/', trim($displayName)), 0, 2) as $part) {
    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
}
$photoUrl = (!empty($currentPhoto) && is_file(__DIR__ . '/' . $currentPhoto)) ? $currentPhoto : '';

function field($user, $key) {
    return htmlspecialchars($user[$key] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Personal Information</title>
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
            background: var(--page);
            color: var(--ink);
            margin: 0;
            min-height: 100vh;
        }

        a:focus-visible, button:focus-visible, input:focus-visible, label:focus-visible {
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
            height: 170px;
            background:
                radial-gradient(circle at 12% 130%, var(--sky) 0 30%, transparent 31%),
                radial-gradient(circle at 88% -20%, #5aa0d8 0 28%, transparent 29%),
                linear-gradient(120deg, var(--navy), var(--blue));
        }

        .wrap {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 28px 60px;
        }

        /* ---------- Form card ---------- */
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 26px;
            box-shadow: 0 10px 28px rgba(31, 78, 121, 0.08);
            margin-top: -70px;
            padding: 0 36px 36px;
            position: relative;
        }

        /* Photo picker */
        .photo-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: -60px;
            text-align: center;
        }
        .avatar-ring {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            padding: 5px;
            background: conic-gradient(var(--sky), var(--blue), var(--navy), var(--sky));
            position: relative;
        }
        .avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 5px solid var(--card);
            background: var(--mist);
            color: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 46px;
            font-weight: 800;
            overflow: hidden;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .camera {
            position: absolute;
            right: 2px;
            bottom: 6px;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--blue);
            color: #fff;
            border: 4px solid var(--card);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.12s;
        }
        .camera:hover { background: var(--navy); }
        .camera svg { width: 18px; height: 18px; }
        #photoInput { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
        #photoInput:focus-visible + .camera-focus { outline: 3px solid var(--navy); }

        .photo-hint { margin: 12px 0 0; font-size: 13.5px; color: var(--ink-soft); }
        .remove-row {
            margin-top: 8px;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--ink-soft);
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }
        .remove-row input { accent-color: var(--blue); }

        h1 {
            margin: 22px 0 4px;
            font-size: 26px;
            font-weight: 800;
            text-align: center;
        }
        .lead {
            margin: 0 0 26px;
            text-align: center;
            color: var(--ink-soft);
            font-size: 15px;
        }

        .alert {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid #f5c2c0;
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14.5px;
            margin-bottom: 22px;
        }

        /* Fields */
        .fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 22px;
        }
        .full { grid-column: 1 / -1; }

        label.field-label {
            display: block;
            margin-bottom: 7px;
            font-weight: 800;
            font-size: 14px;
            color: var(--navy);
        }
        .form-group input[type="text"] {
            width: 100%;
            padding: 13px 15px;
            border: 1.5px solid var(--line);
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            color: var(--ink);
            background: #f8fbff;
            transition: border-color 0.12s, background-color 0.12s;
        }
        .form-group input[type="text"]:hover { border-color: var(--sky); }
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--blue);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 196, 0.15);
        }

        .section-title {
            margin: 10px 0 -4px;
            font-size: 13.5px;
            font-weight: 800;
            color: var(--ink-soft);
            padding-top: 18px;
            border-top: 1px solid var(--line);
        }

        /* Buttons */
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            justify-content: flex-end;
        }
        .btn {
            font-family: inherit;
            font-weight: 800;
            font-size: 15px;
            padding: 13px 28px;
            border-radius: 12px;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.12s, background-color 0.12s;
        }
        .btn:active { transform: scale(0.97); }
        .btn-primary { background: var(--blue); color: #fff; border: none; }
        .btn-primary:hover { background: var(--navy); }
        .btn-secondary { background: var(--card); color: var(--navy); border: 1.5px solid var(--line); }
        .btn-secondary:hover { background: var(--mist); }

        @media (max-width: 700px) {
            .fields { grid-template-columns: 1fr; }
            .card { padding: 0 20px 28px; }
            .actions { flex-direction: column-reverse; }
            .btn { text-align: center; }
        }
        @media (max-width: 520px) {
            .topbar-inner, .wrap { padding-left: 16px; padding-right: 16px; }
            .nav a:not(.logout) { display: none; }
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
            <a href="student_profile.php">My profile</a>
            <a href="student_edit.php" class="active">Edit info</a>
            <a href="index.php" class="logout">Log out</a>
        </nav>
    </div>
</header>

<div class="cover"></div>

<main class="wrap">
    <form class="card" action="student_edit.php" method="POST" enctype="multipart/form-data">

        <div class="photo-area">
            <div class="avatar-ring">
                <div class="avatar" id="avatarBox">
                    <?php if ($photoUrl): ?>
                        <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Your profile photo" id="avatarImg">
                    <?php else: ?>
                        <span id="avatarInitials"><?php echo htmlspecialchars($initials ?: 'S'); ?></span>
                    <?php endif; ?>
                </div>
                <input type="file" id="photoInput" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                <label for="photoInput" class="camera" title="Change photo" aria-label="Change profile photo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l2-3h6l2 3h3v11H4V8Z"/><circle cx="12" cy="13" r="3.5"/></svg>
                </label>
            </div>
            <p class="photo-hint" id="photoHint">JPG, PNG or WebP, up to 2 MB</p>
            <?php if ($photoUrl): ?>
                <label class="remove-row">
                    <input type="checkbox" name="remove_photo" value="1" id="removePhoto"> Remove current photo
                </label>
            <?php endif; ?>
        </div>

        <h1>Edit your information</h1>
        <p class="lead">Update your details and photo, then save to see your profile.</p>

        <?php if ($error): ?>
            <div class="alert" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="fields">
            <div class="form-group">
                <label class="field-label" for="fullname">What is your full name?</label>
                <input type="text" id="fullname" name="fullname" value="<?php echo field($user, 'fullname'); ?>">
            </div>
            <div class="form-group">
                <label class="field-label" for="nickname">What is your nickname?</label>
                <input type="text" id="nickname" name="nickname" value="<?php echo field($user, 'nickname'); ?>">
            </div>
            <div class="form-group full">
                <label class="field-label" for="address">What is your address or city?</label>
                <input type="text" id="address" name="address" value="<?php echo field($user, 'address'); ?>">
            </div>

            <div class="full section-title">School plans</div>

            <div class="form-group">
                <label class="field-label" for="preferred_course_2">What is your 2nd preferred course?</label>
                <input type="text" id="preferred_course_2" name="preferred_course_2" value="<?php echo field($user, 'preferred_course_2'); ?>">
            </div>
            <div class="form-group">
                <label class="field-label" for="preferred_univ_2">What is your 2nd preferred university?</label>
                <input type="text" id="preferred_univ_2" name="preferred_univ_2" value="<?php echo field($user, 'preferred_univ_2'); ?>">
            </div>
            <div class="form-group">
                <label class="field-label" for="favorite_subject">What is your favorite subject?</label>
                <input type="text" id="favorite_subject" name="favorite_subject" value="<?php echo field($user, 'favorite_subject'); ?>">
            </div>
            <div class="form-group">
                <label class="field-label" for="dream_job">What is your dream job in the future?</label>
                <input type="text" id="dream_job" name="dream_job" value="<?php echo field($user, 'dream_job'); ?>">
            </div>

            <div class="full section-title">A little more about you</div>

            <div class="form-group">
                <label class="field-label" for="greatest_fear">What is something you're most afraid of?</label>
                <input type="text" id="greatest_fear" name="greatest_fear" value="<?php echo field($user, 'greatest_fear'); ?>">
            </div>
            <div class="form-group">
                <label class="field-label" for="least_favorite_subject">What is your least favorite subject or activity?</label>
                <input type="text" id="least_favorite_subject" name="least_favorite_subject" value="<?php echo field($user, 'least_favorite_subject'); ?>">
            </div>
        </div>

        <div class="actions">
            <a href="student_dashboard.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
    </form>
</main>

<script>
    // Live preview of the chosen photo + client-side size check
    const input  = document.getElementById('photoInput');
    const box    = document.getElementById('avatarBox');
    const hint   = document.getElementById('photoHint');
    const remove = document.getElementById('removePhoto');

    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            hint.textContent = 'That photo is over 2 MB. Please choose a smaller one.';
            hint.style.color = '#b42318';
            input.value = '';
            return;
        }
        hint.style.color = '';
        hint.textContent = 'New photo selected. Save changes to keep it.';
        if (remove) remove.checked = false;

        const reader = new FileReader();
        reader.onload = e => {
            box.innerHTML = '<img src="' + e.target.result + '" alt="Preview of your new profile photo">';
        };
        reader.readAsDataURL(file);
    });
</script>

</body>
</html>