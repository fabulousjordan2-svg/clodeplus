# Codeplus student management

The sign-in, signup, and student management pages require PHP 8+ with the PDO MySQL extension and MySQL 8+. Do not open `Index.html` with `file://`; Apache must serve this folder so its PHP pages and MySQL connection work.

## Local XAMPP setup

1. Place this project folder at `C:\xampp\htdocs\Codeplus`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`, select **Import**, and import this folder's `schema.sql`. It creates the `codeplus` database, users, fields of study, courses, subjects, student records, grades, attendance, and reporting view.
4. Students sign in at `http://localhost/Codeplus/`. Administrators use `http://localhost/Codeplus/admin-login.php` with the fixed email `Admin@codeplus.local`. Student accounts can only be created by an administrator from the dashboard; public signup is disabled. Each login page accepts only its matching account role, and dashboard logout returns to the matching sign-in page.

For a database created before the student `location` field was added, run this migration once in phpMyAdmin's SQL tab, then re-import `schema.sql` to refresh the reporting view. Re-import `schema.sql` after application updates that change the reporting view:

```sql
ALTER TABLE students ADD COLUMN location VARCHAR(160) NOT NULL DEFAULT '' AFTER email;
```

For an existing installation that predates academic catalogs, import `migration_academic_catalogs.sql` once in phpMyAdmin. It creates fields of study, courses, and subjects, adds the field assignment to student profiles and optional course/subject links to existing grades, and refreshes the reporting view. Existing grades remain available as legacy records. Do not run this one-time migration more than once.

Each field of study belongs to an academy, courses are created under an academy and field, and subjects belong to a course. On an existing installation, first run `migration_academic_catalogs.sql` if it has not already been applied, then import `migration_academies.sql` once. To remove the preloaded `General Academy` and `Codeplus College` catalogs, run `migration_remove_default_academies.sql` after the academy migration. It removes those academies and their fields, courses, and subjects; existing scores are retained but detached from the deleted catalog entries, and student academy/field assignments are cleared. New installations should import `schema.sql` only; admins can add academies and catalog options from the dashboard. Assign an academy and optional field to each student; grade course choices are filtered to their assigned academy and field.

To add instructor assignments on an existing installation, first apply `migration_academic_catalogs.sql` and `migration_academies.sql` if needed, then import `migration_instructors.sql` once. It creates instructor records and course assignments; legacy course teacher-name values remain on their courses and are not converted into instructor records. New installations create these tables from `schema.sql`. Admins can add an instructor and assign one or more courses under a selected academy and field from the dashboard; instructor assignments do not create login accounts.

To add the sample academies, fields, courses, and course codes, import `migration_course_codes.sql` once on an existing installation, then import `college_seed.sql`. The seed script uses the existing `codeplus` schema, assigns each field to an academy, maps courses to fields by name rather than relying on auto-increment IDs, and can be imported again without duplicating the sample data. New installations already have the course-code column in `schema.sql`; import only `college_seed.sql`.

For an existing installation, run `migration_student_profile.sql` once after the academy and academic-catalog migrations. It adds phone, date of birth, and gender to student profiles, teacher names to courses, semester/term labels to attendance records, and refreshes the reporting view. New installations receive these fields from `schema.sql`. Administrators can enter a student's roll number, phone, date of birth, gender, class, and address; course teachers; and the semester/term for attendance entries. Students see their own details, attendance by semester, courses, and marks together on the dashboard. Course cards are built from recorded grades because the database does not store separate course enrollments.

`database.php` connects to MySQL at `127.0.0.1` using database `codeplus`, user `root`, and an empty password by default for local XAMPP development. `db.php` provides the shared academic-catalog query used by the admin-only `api/academics.php` endpoint to return academies, fields of study, courses, subjects, instructors, and their course assignments. If your local MySQL root account has a password, set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` in Apache's PHP environment to match it. Do not expose the XAMPP development server or root MySQL account to the public internet.

Administrators create student accounts from the dashboard. A cryptographically random one-time password is displayed once after creation; admins can also generate a fresh one-time password later from the student login-details panel. Share it securely with the student. Passwords are stored as hashes and cannot be retrieved. Students sign in with the administrator-provided email and password, then may change their own password after confirming the current one. Student login is rejected unless the account is linked to a student record. The public signup page returns HTTP 403, and the student-creation API requires an authenticated administrator session and recent credential confirmation. Student dashboards are read-only for student records, grades, and attendance. Passwords are stored with PHP's `password_hash`, and database queries use prepared statements. Administrators can search student names, emails, numbers, and phone numbers; per-student options provide profile editing, login-email/password reset, grade entry, attendance entry, and activity review. Password hashes cannot be displayed or reversed; the admin login-details form updates the student email and optionally sets a replacement password. The activity table combines grades and attendance and provides edit/remove actions. Admins can create reusable fields of study, courses within each field, and subjects within each course; students can be assigned a field, and grades can be recorded against a course and its subject. Catalog options cannot be removed while they are referenced. Admins can also create, view, edit, and delete student profiles (name, email, student number, phone, date of birth, gender, address, class, academy, and field of study), assign a teacher to each course, record a semester/term for attendance, manage grades, and view reports. The attendance report shows present percentage and present/absent/late/excused counts; the per-student attendance button opens today's entry or the existing entry for editing. Before any student-data change, the administrator must confirm their current credentials; that confirmation lasts 10 minutes. Admins can change only their password from the dashboard; the administrator email is fixed. Student accounts can review only their own student record, grades, attendance, and report. Removing a student also removes that student's grades and attendance records and linked student login.

### Create an administrator

Public signup never grants administrator access. To create the fixed administrator account or reset its forgotten password, run this script from the project folder using a local server shell:

```text
php create_demo_admin.php
```

It creates `Admin@codeplus.local` if it does not exist, or replaces only its password hash if it already exists. If the reserved address belongs to a student account, the script stops without changing that account; first change the student's login email to an available address. A cryptographically generated password is printed once; store it securely and change it from the administrator dashboard after signing in. Access to this script requires access to the server's PHP CLI and database configuration. Do not expose the XAMPP development server or CLI account reset process to the public internet. Student signup and admin-managed student profiles cannot use the reserved administrator email.

For an existing installation whose administrator has a different email, migrate the intended administrator account before using the fixed-email sign-in. First ensure `Admin@codeplus.local` is not already assigned to another account, then update the intended account (replace the old email below):

```sql
UPDATE users
SET email = 'Admin@codeplus.local'
WHERE email = 'admin@school.edu' AND role = 'admin';
```

If upgrading an existing installation created from the earlier schema, add the role column once before using the application:

```sql
ALTER TABLE users ADD COLUMN role ENUM('admin', 'student') NOT NULL DEFAULT 'student';
```

For XAMPP, run the script with `C:\xampp\php\php.exe create_demo_admin.php`. The password is stored as a hash and is never embedded in source.

### API

The JSON endpoints are `api/students.php`, `api/student-account.php`, `api/academics.php`, `api/grades.php`, `api/attendance.php`, `api/reports.php`, `api/admin-reauth.php`, `api/student-password.php`, and `api/admin-password.php`. They require a signed-in session; write requests require the session CSRF token in the `X-CSRF-Token` header. Student accounts receive read-only access to their own student, grade, attendance, and report data; `api/student-password.php` allows a student to change only their own password after validating their current password. `api/admin-password.php` allows an administrator to change only their password after validating the current password. `api/student-account.php` lets an administrator change a linked student's login email and optionally replace their password, including generating a one-time password, after recent credential confirmation. The password hash is never returned. Student profile, academic catalog, grade, and attendance changes are administrator-only and require recent credential confirmation. Grades are validated so scores cannot exceed a positive maximum score; catalog-linked grades must use a subject belonging to the selected course. Attendance allows one status per student per date. Passwords cannot be retrieved from the database; the administrator CLI utility displays a generated password only when creating or resetting the fixed administrator account.