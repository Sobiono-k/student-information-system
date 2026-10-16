# Student Information System

A simple PHP and MySQL web app where students create an account, build a personal profile with a photo, and admins manage the student directory. The interface uses a calm blue theme and a TikTok-style profile layout.

## Features

**Students**
- Register and log in with email and password (passwords are hashed)
- Dashboard styled like a social profile: cover banner, photo avatar, stats and an info card
- Edit personal information, including a profile photo with live preview
- View a full profile page with all answers grouped into cards

**Admins**
- Summary cards for total users, students and admins
- Searchable directory (by email or full name) with photos and role badges
- "View info" pop-up showing a student's photo and all answers
- Delete users, protected by a CSRF token, and the user's photo file is removed too

**General**
- Responsive website layout that works on desktop, tablet and phone
- Role-based redirects after login (student or admin)
- Prepared statements for all database queries

## Pages

| File | Purpose |
|------|---------|
| `index.php` | Login |
| `register.php` | Create an account |
| `student_dashboard.php` | Student home |
| `student_profile.php` | View personal profile |
| `student_edit.php` | Edit information and upload a photo |
| `admin_dashboard.php` | Admin directory and user management |

## Requirements

- PHP 8.0 or newer
- MySQL or MariaDB
- A local server such as XAMPP, WAMP or Laragon

## Setup

1. Copy the project folder into your server directory (for XAMPP, `htdocs/`).
2. Create a database named `login_db` and a `users` table. The columns below are the ones the code uses:

```sql
CREATE DATABASE login_db;
USE login_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'student',
    fullname VARCHAR(255) NULL,
    nickname VARCHAR(100) NULL,
    address VARCHAR(255) NULL,
    preferred_course_2 VARCHAR(255) NULL,
    preferred_univ_2 VARCHAR(255) NULL,
    favorite_subject VARCHAR(255) NULL,
    dream_job VARCHAR(255) NULL,
    greatest_fear VARCHAR(255) NULL,
    least_favorite_subject VARCHAR(255) NULL,
    profile_photo VARCHAR(255) NULL
);
```

If you already have the table, just add the photo column:

```sql
ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL;
```

3. Check the database settings at the top of each PHP file (default is host `localhost`, user `root`, empty password, database `login_db`).
4. Make sure the `uploads/profile/` folder can be written to. It is created automatically on the first photo upload.
5. Open `http://localhost/<your-folder>/index.php` in your browser.

## Profile photos

- Accepted types: JPG, PNG and WebP, up to 2 MB
- Files are checked as real images and saved with random names in `uploads/profile/`
- Replacing or removing a photo deletes the old file

## Notes before using this beyond a class project

- The register page lets anyone choose the **Admin** role. Remove that option and create admin accounts directly in the database.
- Move database credentials into a config file kept out of version control, and use a dedicated database user instead of `root`.
- Add CSRF protection to the other forms and use HTTPS when deployed.

## Tech stack

PHP, MySQL (mysqli), HTML and CSS. No frameworks or build step.
