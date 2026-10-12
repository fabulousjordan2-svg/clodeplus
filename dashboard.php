<?php
declare(strict_types=1);

require __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
	header('Location: Index.html');
	exit;
}

$account = database()->prepare('SELECT name, email, role FROM users WHERE id = :id LIMIT 1');
$account->execute(['id' => (int) $_SESSION['user_id']]);
$currentUser = $account->fetch();
if (!$currentUser) {
	$_SESSION = [];
	session_destroy();
	header('Location: Index.html');
	exit;
}
$_SESSION['user_name'] = $currentUser['name'];
$_SESSION['user_role'] = $currentUser['role'];
$isAdmin = $currentUser['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="theme-color" content="#f8f8f5">
	<meta name="csrf-token" content="<?= escape(csrf_token()) ?>">
	<title><?= $isAdmin ? 'Student management' : 'Student portal' ?> | CODEPLUS</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
	<style>
		:root { --paper: #f8f8f5; --white: #fff; --ink: #202823; --muted: #777e78; --line: #e3e6e0; --green: #28604b; --dark: #1d4838; --red: #a44936; --serif: 'Fraunces', Georgia, serif; --sans: 'DM Sans', 'Segoe UI', sans-serif; }
		* { box-sizing: border-box; }
		body { margin: 0; background: var(--paper); color: var(--ink); font-family: var(--sans); }
		header { display: flex; min-height: 76px; align-items: center; justify-content: space-between; padding: 12px max(24px, calc((100vw - 1120px) / 2)); border-bottom: 1px solid var(--line); background: var(--white); }
		.brand { display: inline-flex; align-items: center; gap: 10px; color: var(--dark); font-size: 18px; font-weight: 700; text-decoration: none; }
		.brand-mark { display: grid; width: 32px; height: 32px; place-items: center; border: 1px solid var(--green); border-radius: 50%; font-family: var(--serif); font-size: 20px; }
		.header-right { display: flex; align-items: center; gap: 15px; color: var(--muted); font-size: 13px; }
		button, input { font: inherit; }
		button { cursor: pointer; }
		.logout, .secondary, .danger, .primary { min-height: 39px; padding: 8px 13px; border: 1px solid var(--line); border-radius: 4px; background: var(--white); color: var(--dark); font-weight: 600; }
		.logout:hover, .secondary:hover { background: #edf1eb; }
		main { width: min(100% - 40px, 1120px); margin: 0 auto; padding: 42px 0 72px; }
		.eyebrow { margin: 0 0 10px; color: var(--green); font-size: 11px; font-weight: 700; letter-spacing: 1.3px; text-transform: uppercase; }
		h1, h2 { font-family: var(--serif); font-weight: 500; }
		h1 { margin: 0; font-size: clamp(38px, 5vw, 56px); line-height: 1.05; }
		h2 { margin: 0 0 20px; font-size: 27px; }
		.intro { margin: 12px 0 30px; color: var(--muted); }
		.panel { margin-top: 24px; padding: 24px; border: 1px solid var(--line); border-radius: 6px; background: var(--white); }
		.form-grid { display: grid; grid-template-columns: repeat(4, minmax(130px, 1fr)); align-items: end; gap: 14px; }
		.field label { display: block; margin-bottom: 7px; font-size: 12px; font-weight: 600; }
		.field input, .field select { width: 100%; min-height: 42px; padding: 9px 11px; border: 1px solid #dfe3dc; border-radius: 4px; background: #fff; color: var(--ink); }
		.field input:focus, .field select:focus { outline: 2px solid rgb(40 96 75 / 20%); border-color: var(--green); }
		.catalog-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
		.catalog-tab[aria-pressed="true"] { border-color: var(--green); background: #edf1eb; color: var(--dark); }
		.academic-form { grid-column: 1 / -1; grid-template-columns: repeat(4, minmax(0, 1fr)); align-items: end; }
		.academic-form[hidden] { display: none; }
		.primary { border-color: var(--green); background: var(--green); color: #fff; }
		.primary:hover { background: var(--dark); }
		.form-actions { display: flex; gap: 8px; }
		.message { min-height: 20px; margin: 12px 0 0; color: var(--red); font-size: 13px; }
		.message.success { color: var(--green); }
		.table-wrap { overflow-x: auto; }
		table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
		th, td { padding: 12px 10px; border-bottom: 1px solid #edf0eb; vertical-align: middle; white-space: nowrap; }
		th { color: var(--muted); font-size: 11px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; }
		.actions { display: flex; gap: 7px; }
		.small { min-height: 32px; padding: 5px 9px; font-size: 12px; }
		.student-search { display: flex; gap: 9px; margin: 0 0 18px; }
		.student-search input { width: min(100%, 380px); min-height: 40px; padding: 8px 11px; border: 1px solid #dfe3dc; border-radius: 4px; }
		.student-search input:focus { outline: 2px solid rgb(40 96 75 / 20%); border-color: var(--green); }
		.student-options { position: relative; }
		.student-options summary { display: grid; width: 36px; height: 34px; place-content: center; gap: 3px; border: 1px solid var(--line); border-radius: 4px; background: var(--white); cursor: pointer; list-style: none; }
		.student-options summary::-webkit-details-marker { display: none; }
		.student-options summary span { width: 15px; height: 2px; border-radius: 2px; background: var(--dark); }
		.student-options-menu { position: absolute; z-index: 5; top: calc(100% + 4px); right: 0; display: grid; min-width: 170px; padding: 5px; border: 1px solid var(--line); border-radius: 5px; background: var(--white); box-shadow: 0 8px 24px rgb(32 40 35 / 14%); }
		.student-options-menu button { padding: 9px 10px; border: 0; border-radius: 3px; background: transparent; color: var(--ink); text-align: left; font-size: 12px; }
		.student-options-menu button:hover { background: #edf1eb; }
		.student-options-menu button.menu-danger { color: var(--red); }
		.admin-menu-toggle { display: grid; width: 42px; height: 42px; flex: 0 0 42px; place-content: center; gap: 5px; border: 1px solid var(--line); border-radius: 5px; background: var(--white); }
		.admin-menu-toggle span { display: block; width: 19px; height: 2px; border-radius: 2px; background: var(--dark); transition: transform .2s ease, opacity .2s ease; }
		.admin-menu-toggle[aria-expanded="true"] span:first-child { transform: translateY(7px) rotate(45deg); }
		.admin-menu-toggle[aria-expanded="true"] span:nth-child(2) { opacity: 0; }
		.admin-menu-toggle[aria-expanded="true"] span:last-child { transform: translateY(-7px) rotate(-45deg); }
		.admin-menu-backdrop { position: fixed; z-index: 19; inset: 0; border: 0; background: rgb(20 35 27 / 42%); opacity: 0; transition: opacity .2s ease; }
		.admin-menu-backdrop[hidden] { display: none; }
		.admin-menu-backdrop.is-visible { opacity: 1; }
		.admin-menu { position: fixed; z-index: 20; inset: 0 auto 0 0; width: min(84vw, 310px); padding: 28px 18px; overflow-y: auto; border-right: 1px solid var(--line); background: var(--white); box-shadow: 12px 0 36px rgb(20 35 27 / 12%); transform: translateX(-105%); transition: transform .22s ease; }
		.admin-menu.is-open { transform: translateX(0); }
		.admin-menu-label { margin: 3px 12px 18px; color: var(--muted); font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
		.admin-menu-list { display: grid; gap: 5px; }
		.admin-menu-list button { display: flex; width: 100%; min-height: 44px; align-items: center; gap: 12px; padding: 10px 12px; border: 0; border-radius: 5px; background: transparent; color: var(--ink); text-align: left; font-size: 13px; font-weight: 600; }
		.admin-menu-list button:hover, .admin-menu-list button:focus-visible { background: #edf1eb; color: var(--dark); }
		.admin-menu-icon { width: 21px; color: var(--green); font-size: 17px; text-align: center; }
		.activity-filter { width: min(100%, 320px); min-height: 40px; margin: 0 0 16px; padding: 8px 11px; border: 1px solid #dfe3dc; border-radius: 4px; background: #fff; color: var(--ink); font: inherit; }
		.activity-kind { display: inline-block; padding: 4px 7px; border-radius: 12px; background: #edf1eb; color: var(--dark); font-size: 11px; font-weight: 700; text-transform: capitalize; }
		.activity-kind.attendance { background: #e8f0f7; color: #174d7a; }
		.danger { border-color: #e8c8c0; color: var(--red); }
		.danger:hover { background: #fff2ee; }
		.empty { padding: 20px 10px; color: var(--muted); text-align: center; }
		.visually-hidden { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; clip-path: inset(50%); }
		.report-note { margin: -10px 0 18px; color: var(--muted); font-size: 13px; line-height: 1.6; }
		.section-nav { display: flex; flex-wrap: wrap; gap: 9px; margin: 0 0 24px; }
		.section-nav a { padding: 9px 13px; border: 1px solid var(--line); border-radius: 4px; background: var(--white); color: var(--dark); font-size: 13px; font-weight: 600; text-decoration: none; }
		.section-nav a:hover { background: #edf1eb; }
		@media (max-width: 760px) { header { min-height: 66px; } .header-right > span { display: none; } main { width: min(100% - 28px, 1120px); padding-top: 30px; } .panel { padding: 18px 14px; } .form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .form-actions { grid-column: 1 / -1; } }
		@media (max-width: 420px) { .form-grid { grid-template-columns: 1fr; } .form-actions { grid-column: auto; } }
		.student-dialog { width: min(100% - 32px, 520px); padding: 26px; border: 1px solid var(--line); border-radius: 14px; background: var(--white); color: var(--ink); box-shadow: 0 24px 70px rgb(0 0 0 / 22%); }
		.student-dialog::backdrop { background: rgb(20 35 27 / 48%); backdrop-filter: blur(3px); }
		.student-dialog-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
		.student-dialog-head .eyebrow { margin-top: 0; }
		.student-dialog-head h2 { margin-bottom: 0; }
		.student-detail-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; margin: 24px 0 0; }
		.student-detail-list dt { margin-bottom: 4px; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; }
		.student-detail-list dd { margin: 0; overflow-wrap: anywhere; font-size: 14px; font-weight: 600; }
		.academic-form { display: grid; align-content: start; gap: 10px; }
		.academic-options ul { display: grid; gap: 7px; margin: 20px 0 0; padding: 0; list-style: none; }
		.academic-options li { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 11px; border: 1px solid var(--line); border-radius: 4px; font-size: 13px; }
		.admin-reauth { width: min(100% - 32px, 440px); padding: 26px; border: 1px solid var(--line); border-radius: 12px; background: var(--white); color: var(--ink); }
		.admin-reauth::backdrop { background: rgb(20 35 27 / 48%); backdrop-filter: blur(3px); }
		.admin-reauth h2 { margin: 0 0 10px; }
		.admin-reauth p { color: var(--muted); font-size: 13px; line-height: 1.55; }
		.admin-reauth .field { margin-top: 15px; }
		.admin-reauth-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
		.student-portal { --paper: #f2f5f7; --white: #fff; --ink: #243244; --muted: #69788a; --line: #dce4ec; --green: #174d7a; --dark: #103858; background: var(--paper); }
		.student-portal header { position: sticky; z-index: 12; top: 0; justify-content: flex-start; gap: 18px; min-height: 72px; box-shadow: 0 2px 8px rgb(28 54 77 / 5%); }
		.student-portal .brand { font-size: 17px; }
		.student-portal .header-right { margin-left: auto; }
		.student-header-actions { display: flex; align-items: center; gap: 7px; }
		.student-header-actions a { display: inline-flex; align-items: center; text-decoration: none; }
		.student-menu-toggle { display: grid; width: 44px; height: 44px; flex: 0 0 44px; place-content: center; gap: 5px; border: 1px solid var(--line); border-radius: 7px; background: var(--white); box-shadow: 0 3px 8px rgb(20 48 73 / 10%); }
		.student-menu-toggle span { display: block; width: 21px; height: 2px; border-radius: 2px; background: var(--green); box-shadow: 0 2px 0 rgb(16 56 88 / 12%); transition: transform .22s ease, opacity .18s ease; }
		.student-menu-toggle[aria-expanded="true"] span:first-child { transform: translateY(7px) rotate(45deg); }
		.student-menu-toggle[aria-expanded="true"] span:nth-child(2) { opacity: 0; }
		.student-menu-toggle[aria-expanded="true"] span:last-child { transform: translateY(-7px) rotate(-45deg); }
		.student-menu-backdrop { position: fixed; z-index: 19; inset: 72px 0 0; border: 0; background: rgb(19 34 49 / 36%); opacity: 0; transition: opacity .2s ease; }
		.student-menu-backdrop[hidden] { display: none; }
		.student-menu-backdrop.is-visible { opacity: 1; }
		.student-menu { position: fixed; z-index: 20; top: 72px; bottom: 0; left: 0; width: min(310px, calc(100vw - 48px)); padding: 23px 15px; overflow-y: auto; background: var(--white); box-shadow: 10px 12px 30px rgb(19 34 49 / 15%); transform: translateX(-105%); transition: transform .24s ease; }
		.student-menu.is-open { transform: translateX(0); }
		.student-menu-label { margin: 0 11px 13px; color: var(--muted); font-size: 10px; font-weight: 700; letter-spacing: 1.25px; text-transform: uppercase; }
		.student-menu-list { display: grid; gap: 5px; }
		.student-menu-list button { display: flex; width: 100%; min-height: 45px; align-items: center; gap: 12px; padding: 9px 11px; border: 0; border-radius: 6px; background: transparent; color: #34485b; cursor: pointer; font: inherit; font-size: 13px; font-weight: 600; text-align: left; }
		.student-menu-list button:hover, .student-menu-list button[aria-current="page"] { background: #eaf1f7; color: var(--green); }
		.student-menu-icon { display: grid; width: 25px; height: 25px; flex: 0 0 25px; place-items: center; border-radius: 5px; background: #f0f4f7; color: var(--green); font-size: 14px; }
		.student-portal main { max-width: 1120px; padding-top: 33px; }
		.student-portal h1 { font-size: clamp(33px, 4vw, 45px); }
		.student-portal .eyebrow { color: #52718d; }
		.student-portal .intro { margin-bottom: 20px; }
		.student-portal .panel { border-radius: 9px; background: var(--white); box-shadow: 0 5px 18px rgb(31 60 84 / 4%); }
		.student-view[hidden] { display: none !important; }
		.student-view .report-note { margin-top: 0; }
		.student-detail-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0 18px; margin: 0; }
		.student-detail-grid div { padding: 14px 0; border-bottom: 1px solid #edf0eb; }
		.student-detail-grid dt { margin-bottom: 5px; color: var(--muted); font-size: 10px; font-weight: 700; letter-spacing: .45px; text-transform: uppercase; }
		.student-detail-grid dd { margin: 0; overflow-wrap: anywhere; font-size: 14px; font-weight: 600; }
		.student-section-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 15px; }
		.student-section-heading h2 { margin-bottom: 8px; }
		.student-dashboard { display: grid; gap: 3px; margin-top: 20px; }
		.student-dashboard > .panel { margin-top: 0; scroll-margin-top: 86px; }
		.student-welcome { border-left: 4px solid var(--green) !important; }
		.student-welcome h2 { margin: 0 0 8px; }
		.student-welcome .eyebrow, .student-section-heading .eyebrow { margin: 0 0 6px; }
		.student-welcome .report-note { margin-bottom: 0; }
		.student-attendance-overall, .student-grade-average { margin: 3px 0 0; padding: 7px 10px; border-radius: 4px; background: var(--green); color: #fff; font-size: 12px; font-weight: 700; white-space: nowrap; }
		.student-subheading { margin: 23px 0 8px; font-size: 15px; }
		.student-course-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
		.student-course-card { padding: 15px; border: 1px solid var(--line); border-radius: 7px; background: #fbfcfd; }
		.student-course-card h3 { margin: 0 0 12px; color: var(--green); font-size: 15px; }
		.student-course-card p { display: flex; justify-content: space-between; gap: 10px; margin: 8px 0 0; font-size: 12px; }
		.student-course-card span:first-child { color: var(--muted); }
		.student-course-card strong { overflow-wrap: anywhere; text-align: right; }
		.student-menu-list button[data-student-section] { border: 0; }
		@media (max-width: 760px) { .student-portal header { min-height: 68px; gap: 11px; padding-right: 14px; padding-left: 14px; } .student-portal .header-right { gap: 8px; } .student-header-actions { display: none; } .student-portal .header-right form button { padding: 7px 9px; font-size: 11px; } .student-menu-backdrop { inset-block-start: 68px; } .student-menu { top: 68px; } .student-detail-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .student-course-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
		@media (max-width: 520px) { .student-portal .brand-mark { width: 29px; height: 29px; } .student-portal .brand > span:last-child { display: none; } .student-portal main { width: min(100% - 24px, 1120px); padding-top: 25px; } .student-detail-grid, .student-course-grid { grid-template-columns: 1fr; } .student-section-heading { flex-direction: column; } }
		@media (prefers-reduced-motion: reduce) { .student-menu-toggle span, .student-menu, .student-menu-backdrop { transition: none; } }
		@media (max-width: 420px) { .student-detail-list { grid-template-columns: 1fr; } }
	</style>
	<link rel="stylesheet" href="api/style.css">
</head>
<body class="<?= $isAdmin ? '' : 'student-portal' ?>">
	<header>
		<?php if ($isAdmin): ?>
			<button class="admin-menu-toggle" id="admin-menu-toggle" type="button" aria-label="Open administration menu" aria-controls="admin-menu" aria-expanded="false">
				<span></span><span></span><span></span>
			</button>
		<?php else: ?>
			<button class="student-menu-toggle" id="student-menu-toggle" type="button" aria-label="Open student navigation" aria-controls="student-menu" aria-expanded="false">
				<span></span><span></span><span></span>
			</button>
		<?php endif; ?>
		<a class="brand" href="dashboard.php" aria-label="Codeplus home"><span class="brand-mark" aria-hidden="true">m</span><span>morrow</span></a>
		<div class="header-right">
			<span><?= escape($currentUser['name']) ?> · <?= $isAdmin ? 'Administrator' : 'Student' ?></span>
			<?php if (!$isAdmin): ?>
				<nav class="student-header-actions" aria-label="Student shortcuts">
					<a class="secondary small" href="dashboard.php">Homepage</a>
					<button class="secondary small" id="student-header-password" type="button">Change password</button>
				</nav>
			<?php endif; ?>
			<form method="post" action="logout.php">
				<input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
				<button class="logout" type="submit"><?= $isAdmin ? 'Admin log out' : 'Student log out' ?></button>
			</form>
		</div>
	</header>
	<?php if ($isAdmin): ?>
		<button class="admin-menu-backdrop" id="admin-menu-backdrop" type="button" aria-label="Close administration menu" hidden></button>
		<aside class="admin-menu" id="admin-menu" aria-label="Administration navigation" aria-hidden="true" inert>
			<p class="admin-menu-label">Administration</p>
			<nav class="admin-menu-list">
				<button type="button" data-admin-destination="search-students"><span class="admin-menu-icon" aria-hidden="true">⌕</span>Search students</button>
				<button type="button" data-admin-destination="add-student"><span class="admin-menu-icon" aria-hidden="true">＋</span>Add a student</button>
				<button type="button" data-admin-destination="student-login"><span class="admin-menu-icon" aria-hidden="true">▣</span>Student login details</button>
				<button type="button" data-admin-destination="academic-catalogs"><span class="admin-menu-icon" aria-hidden="true">⌘</span>Courses and subjects</button>
				<button type="button" data-admin-destination="grades"><span class="admin-menu-icon" aria-hidden="true">▦</span>Add or edit grades</button>
				<button type="button" data-admin-destination="attendance"><span class="admin-menu-icon" aria-hidden="true">◷</span>Record attendance</button>
				<button type="button" data-admin-destination="activities"><span class="admin-menu-icon" aria-hidden="true">▤</span>Student activities</button>
				<button type="button" data-admin-destination="reports"><span class="admin-menu-icon" aria-hidden="true">▥</span>Academic reports</button>
				<button type="button" data-admin-destination="admin-password"><span class="admin-menu-icon" aria-hidden="true">⚙</span>Administrator password</button>
			</nav>
		</aside>
	<?php endif; ?>
	<?php if (!$isAdmin): ?>
		<button class="student-menu-backdrop" id="student-menu-backdrop" type="button" aria-label="Close navigation" hidden></button>
		<aside class="student-menu" id="student-menu" aria-label="Student navigation" aria-hidden="true" inert>
			<p class="student-menu-label">Student portal</p>
			<nav class="student-menu-list">
				<button type="button" data-student-view="overview" aria-current="page"><span class="student-menu-icon" aria-hidden="true">⌂</span>Dashboard</button>
				<button type="button" data-student-section="student-details-panel"><span class="student-menu-icon" aria-hidden="true">▤</span>Student information</button>
				<button type="button" data-student-section="student-attendance-panel"><span class="student-menu-icon" aria-hidden="true">◷</span>Attendance</button>
				<button type="button" data-student-section="student-courses-panel"><span class="student-menu-icon" aria-hidden="true">⌘</span>Classes and courses</button>
				<button type="button" data-student-section="student-marks-panel"><span class="student-menu-icon" aria-hidden="true">▦</span>My marks</button>
				<button type="button" data-student-view="password"><span class="student-menu-icon" aria-hidden="true">⚙</span>Change password</button>
			</nav>
		</aside>
	<?php endif; ?>
	<main>
		<p class="eyebrow"><?= $isAdmin ? 'Administration' : 'Your Codeplus space' ?></p>
		<h1<?= $isAdmin ? '' : ' id="student-page-title"' ?>><?= $isAdmin ? 'Student management' : 'Student dashboard' ?></h1>
		<p class="intro"<?= $isAdmin ? '' : ' id="student-page-intro"' ?>><?= $isAdmin ? 'Manage student records, fields of study, courses, subjects, grades, and reports.' : 'Review your personal information, attendance, courses, and marks.' ?></p>
		<p id="page-message" class="message" role="status" aria-live="polite"></p>
		<?php if ($isAdmin): ?>
			<nav class="section-nav" aria-label="Dashboard sections">
				<a href="#admin-password-title">Administrator password</a>
				<a href="#academic-catalogs">Courses and subjects</a>
				<a href="#student-records">Student records</a>
				<a href="#grades-title">Grades</a>
				<a href="#report-title">Reports</a>
				<a href="#attendance-title">Attendance</a>
			</nav>
			<section class="panel" aria-labelledby="admin-password-title">
				<h2 id="admin-password-title">Administrator password</h2>
				<p class="report-note">Administrator email is fixed as <?= escape(ADMIN_EMAIL) ?>. Only the password can be changed here.</p>
				<form id="admin-password-form">
					<div class="form-grid">
						<div class="field"><label for="admin-current-password">Current password</label><input id="admin-current-password" name="current_password" type="password" autocomplete="current-password" required></div>
						<div class="field"><label for="admin-new-password">New password</label><input id="admin-new-password" name="new_password" type="password" minlength="8" autocomplete="new-password" required></div>
						<div class="field"><label for="admin-password-confirmation">Confirm new password</label><input id="admin-password-confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></div>
						<div class="form-actions"><button class="primary" type="submit">Update password</button></div>
					</div>
				</form>
			</section>
			<section class="panel" id="academic-catalogs" aria-labelledby="academic-catalogs-title">
				<h2 id="academic-catalogs-title">Academies, fields of study, courses, and subjects</h2>
				<p class="report-note">Choose what to add. Fields belong to an academy, courses to a field, and subjects to a course.</p>
				<div class="catalog-tabs" role="group" aria-label="Choose what to add">
					<button class="secondary catalog-tab" type="button" id="catalog-tab-academy" data-catalog-form="academy" aria-pressed="true">Academy</button>
					<button class="secondary catalog-tab" type="button" data-catalog-form="field" aria-pressed="false">Field</button>
					<button class="secondary catalog-tab" type="button" data-catalog-form="course" aria-pressed="false">Course</button>
					<button class="secondary catalog-tab" type="button" data-catalog-form="subject" aria-pressed="false">Subject</button>
					<button class="secondary catalog-tab" type="button" data-catalog-form="instructor" aria-pressed="false">Instructor</button>
				</div>
				<div class="form-grid">
					<form class="academic-form" data-academic-type="academy">
						<div class="field"><label for="academy-name">Add an academy</label><input id="academy-name" name="name" maxlength="120" placeholder="Enter academy name" required></div>
						<div class="form-actions"><button class="primary" type="submit">Add academy</button></div>
					</form>
					<form class="academic-form" data-academic-type="field" hidden>
						<div class="field"><label for="field-academy">Academy</label><select id="field-academy" name="academy_id" required><option value="">Add an academy first</option></select></div>
						<div class="field"><label for="field-name">Add a field of study</label><input id="field-name" name="name" maxlength="120" placeholder="e.g. Science" required></div>
						<div class="form-actions"><button class="primary" type="submit">Add field</button></div>
					</form>
					<form class="academic-form" data-academic-type="course" hidden>
						<div class="field"><label for="course-academy">Academy</label><select id="course-academy" name="academy_id" required><option value="">Select academy</option></select></div>
						<div class="field"><label for="course-field">Field of study</label><select id="course-field" name="field_of_study_id" required><option value="">Select academy first</option></select></div>
						<div class="field"><label for="course-name">Add a course</label><input id="course-name" name="name" maxlength="120" placeholder="e.g. Biology" required></div>
						<div class="field"><label for="course-code">Course code (optional)</label><input id="course-code" name="course_code" maxlength="40" placeholder="e.g. BSc IT"></div>
						<div class="form-actions"><button class="primary" type="submit">Add course</button></div>
					</form>
					<form class="academic-form" data-academic-type="subject" hidden>
						<div class="field"><label for="subject-course">Course</label><select id="subject-course" name="course_id" required><option value="">Add a course first</option></select></div>
						<div class="field"><label for="catalog-subject-name">Add a subject</label><input id="catalog-subject-name" name="name" maxlength="120" placeholder="e.g. Cell biology" required></div>
						<div class="form-actions"><button class="primary" type="submit">Add subject</button></div>
					</form>
					<form class="academic-form" id="instructor-form" data-academic-type="instructor" hidden>
						<div class="field"><label for="instructor-name">Add an instructor</label><input id="instructor-name" name="name" maxlength="120" placeholder="Instructor name" required></div>
						<div class="field"><label for="instructor-academy">Academy</label><select id="instructor-academy" name="academy_id" required><option value="">Select academy</option></select></div>
						<div class="field"><label for="instructor-field">Field of study</label><select id="instructor-field" name="field_of_study_id" required><option value="">Select academy first</option></select></div>
						<div class="field"><label for="instructor-courses">Courses</label><select id="instructor-courses" name="course_ids[]" multiple size="5" required aria-describedby="instructor-course-help"><option value="">Select a field first</option></select><small id="instructor-course-help">Select one or more courses in this field. Instructors do not have sign-in accounts.</small></div>
						<div class="form-actions"><button class="primary" type="submit">Add instructor</button></div>
					</form>
				</div>
				<div id="academic-options" class="academic-options" aria-live="polite"></div>
			</section>
			<section class="panel" id="student-records" aria-labelledby="student-form-title">
				<h2 id="student-form-title">Add a student</h2>
				<form id="student-form">
					<input type="hidden" name="id">
					<div class="form-grid">
						<div class="field"><label for="student-name">Full name</label><input id="student-name" name="full_name" maxlength="120" required></div>
						<div class="field"><label for="student-email">Email</label><input id="student-email" name="email" type="email" maxlength="254" required></div>
						<div class="field"><label for="student-number">Roll number</label><input id="student-number" name="student_number" maxlength="40"></div>
						<div class="field"><label for="student-phone">Phone</label><input id="student-phone" name="phone" type="tel" maxlength="40" autocomplete="tel"></div>
						<div class="field"><label for="student-birth-date">Date of birth</label><input id="student-birth-date" name="date_of_birth" type="date"></div>
						<div class="field"><label for="student-gender">Gender</label><select id="student-gender" name="gender"><option value="">Not specified</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option><option value="prefer_not_to_say">Prefer not to say</option></select></div>
						<div class="field"><label for="student-level">Class</label><input id="student-level" name="grade_level" maxlength="40" placeholder="e.g. BSc IT 1 semester"></div>
						<div class="field"><label for="student-location">Address</label><input id="student-location" name="location" maxlength="160" placeholder="Home or mailing address"></div>
						<div class="field"><label for="student-academy">Academy</label><select id="student-academy" name="academy_id"><option value="">Not assigned</option></select></div>
						<div class="field"><label for="student-field">Field of study</label><select id="student-field" name="field_of_study_id"><option value="">Not assigned</option></select></div>
						<div class="form-actions"><button class="primary" type="submit">Save student</button><button class="secondary" id="student-cancel" type="button" hidden>Cancel edit</button></div>
					</div>
				</form>
			</section>
			<section class="panel" aria-labelledby="students-title">
				<h2 id="students-title">Students</h2>
				<form class="student-search" id="student-search-form" role="search">
					<label class="visually-hidden" for="student-search-input">Search students</label>
					<input id="student-search-input" type="search" placeholder="Search by name, email, roll number, or phone">
					<button class="secondary" type="submit">Search</button>
					<button class="primary" id="add-academy-from-search" type="button">Add academy</button>
				</form>
				<div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Roll number</th><th>Phone</th><th>Class</th><th>Academy</th><th>Field of study</th><th>Actions</th></tr></thead><tbody id="students-body"></tbody></table></div>
			</section>
			<section class="panel" id="student-login-panel" aria-labelledby="student-login-title">
				<h2 id="student-login-title">Student login details</h2>
				<p class="report-note">Login emails can be viewed and changed here. Passwords are stored as secure hashes and cannot be viewed. Generate a one-time password for a student who needs access; they can change it after signing in.</p>
				<form id="student-login-form">
					<div class="form-grid">
						<div class="field"><label for="login-student">Student</label><select id="login-student" name="student_id" required><option value="">Select student</option></select></div>
						<div class="field"><label for="login-current-email">Current login email</label><input id="login-current-email" type="email" readonly placeholder="Select a student"></div>
						<div class="field"><label for="login-new-email">Login email</label><input id="login-new-email" name="email" type="email" maxlength="254" autocomplete="off" required></div>
						<div class="field"><label for="login-new-password">Set new password (optional)</label><input id="login-new-password" name="new_password" type="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep current password"></div>
						<div class="field"><label for="login-password-confirmation">Confirm new password</label><input id="login-password-confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" placeholder="Repeat the replacement password"></div>
						<div class="form-actions"><button class="primary" type="submit">Save login details</button><button class="secondary" id="generate-student-password" type="button">Generate student password</button></div>
					</div>
				</form>
			</section>
			<dialog class="student-dialog" id="student-dialog" aria-labelledby="student-dialog-title">
				<div class="student-dialog-head">
					<div><p class="eyebrow">Student record</p><h2 id="student-dialog-title">Student details</h2></div>
					<button class="secondary small" id="student-dialog-close" type="button" aria-label="Close student details">Close</button>
				</div>
				<dl class="student-detail-list" id="student-detail-list"></dl>
			</dialog>
			<section class="panel" aria-labelledby="grade-form-title">
				<h2 id="grade-form-title">Add a grade</h2>
				<p class="report-note">Choose a student to limit courses to their assigned academy and field of study. Students without an assigned academy or field can use any course.</p>
				<form id="grade-form">
					<input type="hidden" name="id">
					<div class="form-grid">
						<div class="field"><label for="grade-student">Student</label><select id="grade-student" name="student_id" required><option value="">Select student</option></select></div>
						<div class="field"><label for="grade-course">Course</label><select id="grade-course" name="course_id" required><option value="">Select course</option></select></div>
						<div class="field"><label for="grade-subject">Subject</label><select id="grade-subject" name="subject_id" required><option value="">Select course first</option></select><input type="hidden" name="subject"></div>
						<div class="field"><label for="grade-term">Term</label><input id="grade-term" name="term" maxlength="80" placeholder="e.g. Fall 2026" required></div>
						<div class="field"><label for="grade-score">Score</label><input id="grade-score" name="score" type="number" min="0" step="0.01" required></div>
						<div class="field"><label for="grade-max">Maximum score</label><input id="grade-max" name="max_score" type="number" min="0.01" step="0.01" value="100" required></div>
						<div class="form-actions"><button class="primary" type="submit">Save grade</button><button class="secondary" id="grade-cancel" type="button" hidden>Cancel edit</button></div>
					</div>
				</form>
			</section>
			<section class="panel" aria-labelledby="grades-title">
				<h2 id="grades-title">Grades</h2>
				<div class="table-wrap"><table><thead><tr><th>Student</th><th>Academy</th><th>Field of study</th><th>Course</th><th>Subject</th><th>Term</th><th>Score</th><th>Actions</th></tr></thead><tbody id="grades-body"></tbody></table></div>
			</section>
			<section class="panel report-panel" id="report-panel" aria-labelledby="report-title">
				<h2 id="report-title">Academic report warehouse</h2>
				<p class="report-note">Attendance percentage is present days divided by recorded attendance days. A student's button opens today's attendance entry or edit form.</p>
				<div class="table-wrap"><table><thead><tr><th>Student</th><th>Email</th><th>Address</th><th>Class</th><th>Academy</th><th>Field of study</th><th>Grades</th><th>Average</th><th>Total</th><th>Attendance %</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th><th>Actions</th></tr></thead><tbody id="report-body"></tbody></table></div>
			</section>
			<section class="panel" aria-labelledby="attendance-form-title">
				<h2 id="attendance-form-title">Record attendance</h2>
				<p class="report-note">Choose Present or Absent (or another status), then save the dated attendance entry.</p>
				<form id="attendance-form">
					<input type="hidden" name="id">
					<div class="form-grid">
						<div class="field"><label for="attendance-student">Student</label><select id="attendance-student" name="student_id" required><option value="">Select student</option></select></div>
						<div class="field"><label for="attendance-date">Date</label><input id="attendance-date" name="attendance_date" type="date" required></div>
						<div class="field"><label for="attendance-term">Semester / term</label><input id="attendance-term" name="term" maxlength="80" placeholder="e.g. 1st semester 2026-2029" required></div>
						<div class="field"><label for="attendance-status">Status</label><select id="attendance-status" name="status" required><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option><option value="excused">Excused</option></select></div>
						<div class="field"><label for="attendance-note">Note</label><input id="attendance-note" name="note" maxlength="500"></div>
						<div class="form-actions"><button class="primary" type="submit">Save attendance</button><button class="secondary" id="attendance-cancel" type="button" hidden>Cancel edit</button></div>
					</div>
				</form>
			</section>
			<section class="panel" aria-labelledby="attendance-title">
				<h2 id="attendance-title">Attendance records</h2>
				<div class="table-wrap"><table><thead><tr><th>Student</th><th>Date</th><th>Semester / term</th><th>Status</th><th>Note</th><th>Actions</th></tr></thead><tbody id="attendance-body"></tbody></table></div>
			</section>
			<section class="panel" id="student-activities" aria-labelledby="student-activities-title">
				<h2 id="student-activities-title">Student activities</h2>
				<p class="report-note">Review and edit student grades and attendance records in one table.</p>
				<label class="visually-hidden" for="activity-student-filter">Filter activities by student</label>
				<select class="activity-filter" id="activity-student-filter"><option value="">All students</option></select>
				<div class="table-wrap"><table><thead><tr><th>Student</th><th>Activity</th><th>Subject / status</th><th>Term / date</th><th>Result / note</th><th>Actions</th></tr></thead><tbody id="activities-body"></tbody></table></div>
			</section>
			<dialog class="admin-reauth" id="admin-reauth-dialog" aria-labelledby="admin-reauth-title">
				<h2 id="admin-reauth-title">Confirm administrator access</h2>
				<p>Enter your current administrator email and password to authorize changes. Confirmation remains valid for 10 minutes.</p>
				<form id="admin-reauth-form">
					<div class="field"><label for="admin-reauth-email">Administrator email</label><input id="admin-reauth-email" name="email" type="email" value="<?= escape(ADMIN_EMAIL) ?>" autocomplete="username" readonly required></div>
					<div class="field"><label for="admin-reauth-password">Current password</label><input id="admin-reauth-password" name="password" type="password" autocomplete="current-password" required></div>
					<p class="message" id="admin-reauth-message" role="status" aria-live="polite"></p>
					<div class="admin-reauth-actions"><button class="secondary" id="admin-reauth-cancel" type="button">Cancel</button><button class="primary" type="submit">Confirm and continue</button></div>
				</form>
			</dialog>
		<?php else: ?>
			<section class="student-view student-dashboard" id="student-view-overview" aria-label="Student dashboard">
				<section class="panel student-welcome" aria-labelledby="student-welcome-title">
					<p class="eyebrow">From your school</p>
					<h2 id="student-welcome-title">School notices</h2>
					<p class="report-note"><strong>Welcome notice</strong> · Welcome, <?= escape($currentUser['name']) ?>. View your personal information, attendance, courses, and marks below.</p>
				</section>
				<section class="panel" id="student-details-panel" aria-labelledby="student-details-title">
					<h2 id="student-details-title">My student information</h2>
					<dl class="student-detail-grid" id="student-details"><div><dt>Student record</dt><dd>Loading…</dd></div></dl>
				</section>
				<section class="panel" id="student-attendance-panel" aria-labelledby="student-attendance-title">
					<div class="student-section-heading"><div><p class="eyebrow">Attendance record</p><h2 id="student-attendance-title">My attendance</h2></div><p class="student-attendance-overall" id="student-attendance-overall">Overall: —</p></div>
					<p class="report-note">Attendance is calculated from sessions recorded by your school.</p>
					<div class="table-wrap"><table><thead><tr><th>Class</th><th>Semester</th><th>Present / sessions</th><th>Attendance</th></tr></thead><tbody id="attendance-summary-body"></tbody></table></div>
					<h3 class="student-subheading">Attendance history</h3>
					<div class="table-wrap"><table><thead><tr><th>Date</th><th>Semester / term</th><th>Status</th><th>Note</th></tr></thead><tbody id="attendance-body"></tbody></table></div>
				</section>
				<section class="panel" id="student-courses-panel" aria-labelledby="student-courses-title">
					<h2 id="student-courses-title">My classes and courses</h2>
					<p class="report-note">Courses and terms are shown from your recorded marks.</p>
					<div class="student-course-grid" id="student-courses"></div>
				</section>
				<section class="panel" id="student-marks-panel" aria-labelledby="student-grades-title">
					<div class="student-section-heading"><div><p class="eyebrow">Academic record</p><h2 id="student-grades-title">My marks</h2></div><p class="student-grade-average" id="student-grade-average">Average: —</p></div>
					<div class="table-wrap"><table><thead><tr><th>Academy</th><th>Course</th><th>Subject</th><th>Term</th><th>Score</th><th>Maximum</th><th>Percentage</th></tr></thead><tbody id="grades-body"></tbody></table></div>
				</section>
			</section>
			<section class="student-view panel" id="student-view-password" aria-labelledby="student-password-title" hidden>
				<h2 id="student-password-title">Change password</h2>
				<p class="report-note">Choose a new password of at least 8 characters. Your password is stored securely and cannot be viewed by administrators.</p>
				<form id="student-password-form">
					<div class="form-grid">
						<div class="field"><label for="student-current-password">Current password</label><input id="student-current-password" name="current_password" type="password" autocomplete="current-password" required></div>
						<div class="field"><label for="student-new-password">New password</label><input id="student-new-password" name="new_password" type="password" minlength="8" autocomplete="new-password" required></div>
						<div class="field"><label for="student-password-confirmation">Confirm new password</label><input id="student-password-confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></div>
						<div class="form-actions"><button class="primary" type="submit">Update password</button></div>
					</div>
				</form>
			</section>
		<?php endif; ?>
	</main>
	<script>
		const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
		let CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
		const pageMessage = document.getElementById('page-message');
		const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character]));
		const formatGender = value => value ? String(value).replace(/_/g, ' ').replace(/^\w/, letter => letter.toUpperCase()) : 'Not specified';
		async function api(path, method = 'GET', body = null) {
			const options = {method, headers: {'Accept': 'application/json'}};
			if (body !== null) {
				options.headers['Content-Type'] = 'application/json';
				options.headers['X-CSRF-Token'] = CSRF_TOKEN;
				options.body = JSON.stringify(body);
			}
			const response = await fetch(path, options);
			const result = await response.json();
			if (!response.ok) throw new Error(result.error || 'The request failed.');
			return result;
		}
		let adminVerificationExpiresAt = 0;
		let adminVerificationResolver = null;
		let adminVerificationPromise = null;
		function requireAdminVerification() {
			if (Date.now() < adminVerificationExpiresAt) return Promise.resolve(true);
			if (adminVerificationPromise) return adminVerificationPromise;
			const dialog = document.getElementById('admin-reauth-dialog');
			document.getElementById('admin-reauth-message').textContent = '';
			dialog.showModal();
			adminVerificationPromise = new Promise(resolve => { adminVerificationResolver = resolve; });
			return adminVerificationPromise;
		}
		function finishAdminVerification(verified) {
			if (!adminVerificationResolver) return;
			const resolve = adminVerificationResolver;
			adminVerificationResolver = null;
			adminVerificationPromise = null;
			resolve(verified);
		}
		async function adminApi(path, method, body) {
			if (method !== 'GET' && !(await requireAdminVerification())) {
				throw new Error('Administrator confirmation is required to make changes.');
			}
			return api(path, method, body);
		}
		function message(text, success = false) {
			pageMessage.textContent = text;
			pageMessage.classList.toggle('success', success);
		}
		function rowEmpty(body, columns, text) {
			body.innerHTML = `<tr><td class="empty" colspan="${columns}">${escapeHtml(text)}</td></tr>`;
		}
		function renderDefinitionList(fields) {
			return fields.map(([label, value]) =>
				`<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value)}</dd></div>`
			).join('');
		}
		function animateReportUpdate() {
			const reportPanel = document.getElementById('report-panel');
			reportPanel.classList.remove('report-updated');
			void reportPanel.offsetWidth;
			reportPanel.classList.add('report-updated');
		}

		if (IS_ADMIN) {
			const adminMenu = document.getElementById('admin-menu');
			const adminMenuToggle = document.getElementById('admin-menu-toggle');
			const adminMenuBackdrop = document.getElementById('admin-menu-backdrop');
			function setAdminMenuOpen(open) {
				adminMenuToggle.setAttribute('aria-expanded', String(open));
				adminMenuToggle.setAttribute('aria-label', open ? 'Close administration menu' : 'Open administration menu');
				adminMenu.classList.toggle('is-open', open);
				adminMenu.setAttribute('aria-hidden', String(!open));
				adminMenu.inert = !open;
				if (open) {
					adminMenuBackdrop.hidden = false;
					window.requestAnimationFrame(() => adminMenuBackdrop.classList.add('is-visible'));
					adminMenu.querySelector('button').focus();
				} else {
					adminMenuBackdrop.classList.remove('is-visible');
					adminMenuToggle.focus();
					window.setTimeout(() => {
						if (adminMenuToggle.getAttribute('aria-expanded') === 'false') adminMenuBackdrop.hidden = true;
					}, 220);
				}
			}
			adminMenuToggle.addEventListener('click', () => {
				setAdminMenuOpen(adminMenuToggle.getAttribute('aria-expanded') !== 'true');
			});
			adminMenuBackdrop.addEventListener('click', () => setAdminMenuOpen(false));
			adminMenu.addEventListener('click', event => {
				const button = event.target.closest('[data-admin-destination]');
				if (!button) return;
				const destinations = {
					'search-students': ['students-title', 'student-search-input'],
					'add-student': ['student-form-title', 'student-name'],
					'student-login': ['student-login-title', 'login-student'],
					'academic-catalogs': ['academic-catalogs-title', 'catalog-tab-academy'],
					grades: ['grade-form-title', 'grade-student'],
					attendance: ['attendance-form-title', 'attendance-student'],
					activities: ['student-activities-title', 'activity-student-filter'],
					reports: ['report-title', null],
					'admin-password': ['admin-password-title', 'admin-current-password']
				};
				const [targetId, focusId] = destinations[button.dataset.adminDestination];
				setAdminMenuOpen(false);
				document.getElementById(targetId).scrollIntoView({behavior: 'smooth', block: 'center'});
				if (focusId) window.setTimeout(() => document.getElementById(focusId).focus({preventScroll: true}), 230);
			});
			document.addEventListener('keydown', event => {
				if (event.key === 'Escape' && adminMenuToggle.getAttribute('aria-expanded') === 'true') {
					setAdminMenuOpen(false);
				}
			});
			const reauthDialog = document.getElementById('admin-reauth-dialog');
			const reauthForm = document.getElementById('admin-reauth-form');
			reauthForm.addEventListener('submit', async event => {
				event.preventDefault();
				const messageNode = document.getElementById('admin-reauth-message');
				const data = Object.fromEntries(new FormData(reauthForm));
				try {
					await api('api/admin-reauth.php', 'POST', data);
					adminVerificationExpiresAt = Date.now() + 10 * 60 * 1000;
					reauthForm.elements.password.value = '';
					messageNode.textContent = '';
					finishAdminVerification(true);
					reauthDialog.close();
				} catch (error) {
					messageNode.textContent = error.message;
				}
			});
			document.getElementById('admin-reauth-cancel').addEventListener('click', () => reauthDialog.close());
			reauthDialog.addEventListener('close', () => {
				finishAdminVerification(false);
				reauthForm.elements.password.value = '';
			});
			const studentForm = document.getElementById('student-form');
			const gradeForm = document.getElementById('grade-form');
			const attendanceForm = document.getElementById('attendance-form');
			const adminPasswordForm = document.getElementById('admin-password-form');
			const studentLoginForm = document.getElementById('student-login-form');
			const loginStudentSelect = document.getElementById('login-student');
			const loginCurrentEmail = document.getElementById('login-current-email');
			const loginNewEmail = document.getElementById('login-new-email');
			const studentsBody = document.getElementById('students-body');
			const gradesBody = document.getElementById('grades-body');
			const attendanceBody = document.getElementById('attendance-body');
			const activitiesBody = document.getElementById('activities-body');
			const reportBody = document.getElementById('report-body');
			const studentSearchForm = document.getElementById('student-search-form');
			const studentSearchInput = document.getElementById('student-search-input');
			const activityStudentFilter = document.getElementById('activity-student-filter');
			let students = [];
			let grades = [];
			let attendance = [];
			let academicOptions = {academies: [], fields_of_study: [], courses: [], subjects: [], instructors: [], instructor_courses: []};
			let studentSearchTerm = '';

			function resetForm(form, cancelId, title, addTitle, buttonText) {
				form.reset();
				if (form.elements.id) form.elements.id.value = '';
				if (form.id === 'grade-form') {
					const legacyCourse = form.elements.course_id.querySelector('option[value="legacy"]');
					if (legacyCourse) legacyCourse.remove();
					updateGradeSubjects();
				}
				document.getElementById(cancelId).hidden = true;
				document.getElementById(title).textContent = addTitle;
				form.querySelector('button[type="submit"]').textContent = buttonText;
			}
			function fillAcademicSelect(select, firstOption, options, selectedValue = '') {
				select.innerHTML = `<option value="">${firstOption}</option>` + options.map(option => {
					const label = option.academy
						? `${option.name} · ${option.academy}${option.field_of_study ? ` / ${option.field_of_study}` : ''}`
						: option.name;
					return `<option value="${option.id}">${escapeHtml(option.course_code ? `${label} · ${option.course_code}` : label)}</option>`;
				}).join('');
				if (options.some(option => String(option.id) === String(selectedValue))) {
					select.value = String(selectedValue);
				}
			}
			function updateGradeSubjects(selectedSubjectId = '') {
				const courseId = gradeForm.elements.course_id.value;
				const subjects = academicOptions.subjects.filter(subject => String(subject.course_id) === courseId);
				fillAcademicSelect(gradeForm.elements.subject_id, courseId ? 'Select subject' : 'Select course first', subjects, selectedSubjectId);
			}
			function updateGradeCourses(selectedCourseId = gradeForm.elements.course_id.value) {
				const student = students.find(item => String(item.id) === gradeForm.elements.student_id.value);
				const courses = academicOptions.courses.filter(course =>
					(!student || !student.academy_id || String(course.academy_id) === String(student.academy_id))
					&& (!student || !student.field_of_study_id || String(course.field_of_study_id) === String(student.field_of_study_id))
				);
				fillAcademicSelect(gradeForm.elements.course_id, 'Select course', courses, selectedCourseId);
				updateGradeSubjects();
			}
			function updateCourseFields(selectedFieldId = document.getElementById('course-field').value) {
				const academyId = document.getElementById('course-academy').value;
				const fields = academicOptions.fields_of_study.filter(field => String(field.academy_id) === academyId);
				fillAcademicSelect(document.getElementById('course-field'), academyId ? 'Select field of study' : 'Select academy first', fields, selectedFieldId);
			}
			function updateStudentFields(selectedFieldId = document.getElementById('student-field').value) {
				const academyId = document.getElementById('student-academy').value;
				const fields = academicOptions.fields_of_study.filter(field => String(field.academy_id) === academyId);
				fillAcademicSelect(document.getElementById('student-field'), academyId ? 'Not assigned' : 'Select academy first', fields, selectedFieldId);
			}
			function updateInstructorFields(selectedFieldId = document.getElementById('instructor-field').value) {
				const academyId = document.getElementById('instructor-academy').value;
				const fields = academicOptions.fields_of_study.filter(field => String(field.academy_id) === academyId);
				fillAcademicSelect(document.getElementById('instructor-field'), academyId ? 'Select field of study' : 'Select academy first', fields, selectedFieldId);
				updateInstructorCourses();
			}
			function updateInstructorCourses() {
				const academyId = document.getElementById('instructor-academy').value;
				const fieldId = document.getElementById('instructor-field').value;
				const select = document.getElementById('instructor-courses');
				const selectedIds = new Set(Array.from(select.selectedOptions, option => option.value));
				const courses = academicOptions.courses.filter(course =>
					String(course.academy_id) === academyId && String(course.field_of_study_id) === fieldId
				);
				select.innerHTML = courses.length
					? courses.map(course => `<option value="${course.id}">${escapeHtml(course.course_code ? `${course.name} · ${course.course_code}` : course.name)}</option>`).join('')
					: '<option value="" disabled>No courses in this field yet</option>';
				Array.from(select.options).forEach(option => {
					option.selected = selectedIds.has(option.value);
				});
			}
			function renderAcademicOptions(result) {
				academicOptions = result;
				const academySelects = [
					{select: document.getElementById('field-academy'), placeholder: 'Select academy'},
					{select: document.getElementById('course-academy'), placeholder: 'Select academy'},
					{select: document.getElementById('student-academy'), placeholder: 'Not assigned'},
					{select: document.getElementById('instructor-academy'), placeholder: 'Select academy'}
				];
				academySelects.forEach(({select, placeholder}) => fillAcademicSelect(select, placeholder, academicOptions.academies, select.value));
				updateCourseFields();
				updateStudentFields();
				updateInstructorFields();
				fillAcademicSelect(document.getElementById('subject-course'), 'Select course', academicOptions.courses, document.getElementById('subject-course').value);
				updateGradeCourses();
				const rows = [
					...academicOptions.academies.map(academy => ({type: 'academy', id: academy.id, label: `Academy · ${academy.name}`})),
					...academicOptions.fields_of_study.map(field => ({type: 'field', id: field.id, label: `Field of study · ${field.name} (${field.academy})`})),
					...academicOptions.courses.map(course => ({type: 'course', id: course.id, label: `Course · ${course.name}${course.course_code ? ` · ${course.course_code}` : ''}${course.teacher_name ? ` · ${course.teacher_name}` : ''} (${course.field_of_study} · ${course.academy})`})),
					...academicOptions.subjects.map(subject => ({type: 'subject', id: subject.id, label: `Subject · ${subject.name} (${subject.course_name} · ${subject.academy})`})),
					...academicOptions.instructors.map(instructor => {
						const assignedCourses = academicOptions.instructor_courses.filter(item => String(item.instructor_id) === String(instructor.id));
						const assignmentLabel = assignedCourses.length
							? assignedCourses.map(item => `${item.course_name} (${item.field_of_study} · ${item.academy})`).join(', ')
							: 'No courses assigned';
						return {type: 'instructor', id: instructor.id, label: `Instructor · ${instructor.name} — ${assignmentLabel}`};
					})
				];
				document.getElementById('academic-options').innerHTML = rows.length
					? `<ul>${rows.map(option => `<li><span>${escapeHtml(option.label)}</span><button class="danger small" type="button" data-delete-academic="${option.type}" data-academic-id="${option.id}">Remove</button></li>`).join('')}</ul>`
					: '<p class="report-note">No academic options have been added yet.</p>';
			}
			function renderStudentRows() {
				const query = studentSearchTerm.toLocaleLowerCase();
				const filteredStudents = students.filter(student =>
					[student.full_name, student.email, student.student_number, student.phone, student.field_of_study]
						.some(value => String(value || '').toLocaleLowerCase().includes(query))
				);
				studentsBody.innerHTML = filteredStudents.length ? filteredStudents.map(student => `<tr>
					<td>${escapeHtml(student.full_name)}</td><td>${escapeHtml(student.email)}</td>
					<td>${escapeHtml(student.student_number || '—')}</td><td>${escapeHtml(student.phone || '—')}</td><td>${escapeHtml(student.grade_level || '—')}</td><td>${escapeHtml(student.academy || '—')}</td><td>${escapeHtml(student.field_of_study || '—')}</td>
					<td><details class="student-options"><summary aria-label="Options for ${escapeHtml(student.full_name)}"><span></span><span></span><span></span></summary>
						<div class="student-options-menu">
							<button type="button" data-view-student="${student.id}">View profile</button>
							<button type="button" data-edit-student="${student.id}">Edit student</button>
							<button type="button" data-edit-student-login="${student.id}">Manage login</button>
							<button type="button" data-student-grades="${student.id}">Add a grade</button>
							<button type="button" data-record-attendance="${student.id}">Add attendance</button>
							<button type="button" data-show-activities="${student.id}">View activities</button>
							<button class="menu-danger" type="button" data-delete-student="${student.id}">Delete student</button>
						</div>
					</details></td></tr>`).join('') : `<tr><td class="empty" colspan="8">${students.length ? 'No students match your search.' : 'No student records yet.'}</td></tr>`;
			}
			function renderStudentActivities() {
				const selectedStudentId = activityStudentFilter.value;
				const activityRows = [
					...grades.map(grade => ({kind: 'grade', record: grade})),
					...attendance.map(record => ({kind: 'attendance', record}))
				].filter(({record}) => !selectedStudentId || String(record.student_id) === selectedStudentId)
					.sort((left, right) => String(right.record.updated_at || '').localeCompare(String(left.record.updated_at || '')));
				activitiesBody.innerHTML = activityRows.length ? activityRows.map(({kind, record}) => {
					const isGrade = kind === 'grade';
					const scorePercent = isGrade ? (Number(record.score) / Number(record.max_score) * 100).toFixed(2) + '%' : '';
					return `<tr>
						<td>${escapeHtml(record.student_name)}</td>
						<td><span class="activity-kind ${kind}">${kind}</span></td>
						<td>${escapeHtml(isGrade ? `${record.course_name ? `${record.course_name} · ` : ''}${record.subject}${record.course_academy ? ` · ${record.course_academy}` : ''}` : record.status)}</td>
						<td>${escapeHtml(isGrade ? record.term : `${record.term || 'Term not assigned'} · ${record.attendance_date}`)}</td>
						<td>${escapeHtml(isGrade ? `${record.score} / ${record.max_score} (${scorePercent})` : (record.note || '—'))}</td>
						<td class="actions">
							<button class="secondary small" type="button" data-edit-activity="${kind}" data-activity-id="${record.id}">Edit</button>
							<button class="danger small" type="button" data-delete-activity="${kind}" data-activity-id="${record.id}">Remove</button>
						</td></tr>`;
				}).join('') : '<tr><td class="empty" colspan="6">No grade or attendance activities found.</td></tr>';
			}
			function prepareGradeForm(studentId, grade = null) {
				resetForm(gradeForm, 'grade-cancel', 'grade-form-title', 'Add a grade', 'Save grade');
				if (grade) {
					for (const key of ['id', 'student_id', 'term', 'score', 'max_score']) gradeForm.elements[key].value = grade[key];
					if (grade.course_id && grade.subject_id) {
						updateGradeCourses(String(grade.course_id));
						updateGradeSubjects(grade.subject_id);
					} else {
						const legacyCourse = new Option('Existing grade (no course)', 'legacy', true, true);
						gradeForm.elements.course_id.add(legacyCourse);
						const legacySubject = new Option(grade.subject, 'legacy', true, true);
						gradeForm.elements.subject_id.add(legacySubject);
						gradeForm.elements.subject.value = grade.subject;
					}
					document.getElementById('grade-form-title').textContent = 'Edit grade';
					document.getElementById('grade-cancel').hidden = false;
					gradeForm.querySelector('button[type="submit"]').textContent = 'Update grade';
				} else {
					gradeForm.elements.student_id.value = studentId;
					updateGradeCourses();
					document.getElementById('grade-max').value = '100';
				}
				document.getElementById('grade-form-title').scrollIntoView({behavior: 'smooth', block: 'center'});
				document.getElementById('grade-course').focus({preventScroll: true});
			}
			function prepareAttendanceForm(studentId, record = null) {
				resetForm(attendanceForm, 'attendance-cancel', 'attendance-form-title', 'Record attendance', 'Save attendance');
				if (record) {
					for (const key of ['id', 'student_id', 'attendance_date', 'term', 'status', 'note']) {
						attendanceForm.elements[key].value = record[key] || '';
					}
					document.getElementById('attendance-form-title').textContent = 'Edit attendance';
					document.getElementById('attendance-cancel').hidden = false;
					attendanceForm.querySelector('button[type="submit"]').textContent = 'Update attendance';
				} else {
					const now = new Date();
					now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
					attendanceForm.elements.student_id.value = studentId;
					attendanceForm.elements.attendance_date.value = now.toISOString().slice(0, 10);
				}
				document.getElementById('attendance-form-title').scrollIntoView({behavior: 'smooth', block: 'center'});
				attendanceForm.elements.status.focus({preventScroll: true});
			}
			async function loadAdminData() {
				try {
					const [studentResult, gradeResult, attendanceResult, reportResult, academicResult] = await Promise.all([
						api('api/students.php'), api('api/grades.php'), api('api/attendance.php'), api('api/reports.php'), api('api/academics.php')
					]);
					students = studentResult.students;
					grades = gradeResult.grades;
					attendance = attendanceResult.attendance;
					renderAcademicOptions(academicResult);
					renderStudentRows();
					const selectedActivityStudent = activityStudentFilter.value;
					activityStudentFilter.innerHTML = '<option value="">All students</option>' + students.map(student =>
						`<option value="${student.id}">${escapeHtml(student.full_name)} (${escapeHtml(student.email)})</option>`
					).join('');
					if (students.some(student => String(student.id) === selectedActivityStudent)) {
						activityStudentFilter.value = selectedActivityStudent;
					}
					const selectedLoginStudent = loginStudentSelect.value;
					loginStudentSelect.innerHTML = '<option value="">Select student</option>' + students.map(student =>
						`<option value="${student.id}">${escapeHtml(student.full_name)} (${escapeHtml(student.email)})${student.user_id ? '' : ' — no login'}</option>`
					).join('');
					if (students.some(student => String(student.id) === selectedLoginStudent)) {
						loginStudentSelect.value = selectedLoginStudent;
					}
					updateLoginEmailFields();
					document.getElementById('grade-student').innerHTML = '<option value="">Select student</option>' + students.map(student => `<option value="${student.id}">${escapeHtml(student.full_name)} (${escapeHtml(student.email)})</option>`).join('');
					document.getElementById('attendance-student').innerHTML = '<option value="">Select student</option>' + students.map(student => `<option value="${student.id}">${escapeHtml(student.full_name)} (${escapeHtml(student.email)})</option>`).join('');
					gradesBody.innerHTML = grades.length ? grades.map(grade => `<tr>
						<td>${escapeHtml(grade.student_name)}</td><td>${escapeHtml(grade.course_academy || '—')}</td><td>${escapeHtml(grade.field_of_study || '—')}</td><td>${escapeHtml(grade.course_name || '—')}</td>
						<td>${escapeHtml(grade.subject)}</td><td>${escapeHtml(grade.term)}</td>
						<td>${escapeHtml(grade.score)} / ${escapeHtml(grade.max_score)}</td>
						<td class="actions"><button class="secondary small" data-edit-grade="${grade.id}">Edit</button><button class="danger small" data-delete-grade="${grade.id}">Remove</button></td></tr>`).join('') : '<tr><td class="empty" colspan="8">No grades yet.</td></tr>';
					attendanceBody.innerHTML = attendance.length ? attendance.map(record => `<tr>
						<td>${escapeHtml(record.student_name)}</td><td>${escapeHtml(record.attendance_date)}</td><td>${escapeHtml(record.term || '—')}</td><td>${escapeHtml(record.status)}</td><td>${escapeHtml(record.note || '—')}</td>
						<td class="actions"><button class="secondary small" data-edit-attendance="${record.id}">Edit</button><button class="danger small" data-delete-attendance="${record.id}">Remove</button></td></tr>`).join('') : '<tr><td class="empty" colspan="6">No attendance records yet.</td></tr>';
					renderStudentActivities();
					reportBody.innerHTML = reportResult.report.length ? reportResult.report.map(record => `<tr>
						<td>${escapeHtml(record.full_name)}</td><td>${escapeHtml(record.email)}</td><td>${escapeHtml(record.location || '—')}</td><td>${escapeHtml(record.grade_level || '—')}</td>
						<td>${escapeHtml(record.academy || '—')}</td><td>${escapeHtml(record.field_of_study || '—')}</td><td>${escapeHtml(record.grade_count)}</td><td>${record.average_percentage === null ? '—' : `${escapeHtml(record.average_percentage)}%`}</td>
						<td>${escapeHtml(record.total_score)} / ${escapeHtml(record.total_possible)}</td>
						<td>${record.attendance_percentage === null ? '—' : `${escapeHtml(record.attendance_percentage)}%`}</td>
						<td>${escapeHtml(record.present_count)}</td><td>${escapeHtml(record.absent_count)}</td><td>${escapeHtml(record.late_count)}</td><td>${escapeHtml(record.excused_count)}</td>
						<td><button class="secondary small" type="button" data-record-attendance="${record.student_id}">Record attendance</button></td></tr>`).join('') : '<tr><td class="empty" colspan="15">No report data yet.</td></tr>';
					animateReportUpdate();
				} catch (error) {
					message(error.message);
				}
			}
			studentSearchForm.addEventListener('submit', event => {
				event.preventDefault();
				studentSearchTerm = studentSearchInput.value.trim();
				renderStudentRows();
			});
			document.querySelectorAll('.academic-form').forEach(form => form.addEventListener('submit', async event => {
				event.preventDefault();
				const formData = new FormData(form);
				const data = Object.fromEntries(formData);
				data.type = form.dataset.academicType;
				if (data.type === 'instructor') {
					data.course_ids = formData.getAll('course_ids[]');
					delete data['course_ids[]'];
				}
				try {
					await adminApi('api/academics.php', 'POST', data);
					form.reset();
					message('Academic option added.', true);
					await loadAdminData();
				} catch (error) { message(error.message); }
			}));
			document.querySelectorAll('[data-catalog-form]').forEach(tab => tab.addEventListener('click', () => {
				document.querySelectorAll('[data-catalog-form]').forEach(option => {
					const isSelected = option === tab;
					option.setAttribute('aria-pressed', String(isSelected));
					document.querySelector(`[data-academic-type="${option.dataset.catalogForm}"]`).hidden = !isSelected;
				});
			}));
			document.getElementById('add-academy-from-search').addEventListener('click', () => {
				document.querySelector('[data-catalog-form="academy"]').click();
				document.getElementById('academic-catalogs').scrollIntoView({behavior: 'smooth', block: 'center'});
				window.setTimeout(() => document.getElementById('academy-name').focus({preventScroll: true}), 230);
			});
			document.getElementById('course-academy').addEventListener('change', () => updateCourseFields());
			document.getElementById('student-academy').addEventListener('change', () => updateStudentFields());
			document.getElementById('instructor-academy').addEventListener('change', () => updateInstructorFields());
			document.getElementById('instructor-field').addEventListener('change', () => updateInstructorCourses());
			document.getElementById('course-field').addEventListener('change', () => {
				const academyId = document.getElementById('course-academy').value;
				const field = academicOptions.fields_of_study.find(option => String(option.id) === document.getElementById('course-field').value);
				if (field && String(field.academy_id) !== academyId) updateCourseFields();
			});
			document.getElementById('academic-options').addEventListener('click', async event => {
				const button = event.target.closest('[data-delete-academic]');
				if (!button) return;
				if (!confirm('Remove this academic option?')) return;
				try {
					await adminApi('api/academics.php', 'DELETE', {type: button.dataset.deleteAcademic, id: button.dataset.academicId});
					message('Academic option removed.', true);
					await loadAdminData();
				} catch (error) { message(error.message); }
			});
			gradeForm.elements.course_id.addEventListener('change', () => updateGradeSubjects());
			gradeForm.elements.student_id.addEventListener('change', () => updateGradeCourses());
			activityStudentFilter.addEventListener('change', renderStudentActivities);
			function updateLoginEmailFields() {
				const student = students.find(item => String(item.id) === loginStudentSelect.value);
				const hasLogin = Boolean(student && student.user_id);
				loginCurrentEmail.value = student ? student.email : '';
				loginNewEmail.value = student ? student.email : '';
				loginCurrentEmail.placeholder = student && !hasLogin ? 'No linked login account' : 'Select a student';
				loginNewEmail.disabled = !hasLogin;
				document.getElementById('login-new-password').disabled = !hasLogin;
				document.getElementById('login-password-confirmation').disabled = !hasLogin;
				studentLoginForm.querySelector('button[type="submit"]').disabled = !hasLogin;
				document.getElementById('generate-student-password').disabled = !hasLogin;
				if (student && !hasLogin) {
					message('This student does not have a linked login account.', false);
				}
			}
			loginStudentSelect.addEventListener('change', updateLoginEmailFields);
			document.getElementById('generate-student-password').addEventListener('click', async () => {
				const student = students.find(item => String(item.id) === loginStudentSelect.value);
				if (!student || !student.user_id) return;
				try {
					const result = await adminApi('api/student-account.php', 'PUT', {
						student_id: student.id,
						email: loginNewEmail.value,
						generate_password: true
					});
					message(`${result.message} One-time password: ${result.temporary_password}`, true);
					await loadAdminData();
					loginStudentSelect.value = String(student.id);
					updateLoginEmailFields();
				} catch (error) { message(error.message); }
			});

			adminPasswordForm.addEventListener('submit', async event => {
				event.preventDefault();
				const data = Object.fromEntries(new FormData(adminPasswordForm));
				try {
					const result = await api('api/admin-password.php', 'PUT', data);
					CSRF_TOKEN = result.csrf_token;
					document.querySelector('meta[name="csrf-token"]').content = CSRF_TOKEN;
					document.querySelector('form[action="logout.php"] input[name="csrf_token"]').value = CSRF_TOKEN;
					adminVerificationExpiresAt = 0;
					adminPasswordForm.reset();
					message('Your administrator password was changed. Confirm your new password before making further updates.', true);
				} catch (error) { message(error.message); }
			});
			studentLoginForm.addEventListener('submit', async event => {
				event.preventDefault();
				const data = Object.fromEntries(new FormData(studentLoginForm));
				try {
					const result = await adminApi('api/student-account.php', 'PUT', data);
					message(result.message, true);
					await loadAdminData();
					loginStudentSelect.value = data.student_id;
					updateLoginEmailFields();
					document.getElementById('login-new-password').value = '';
					document.getElementById('login-password-confirmation').value = '';
				} catch (error) { message(error.message); }
			});

			reportBody.addEventListener('click', event => {
				const button = event.target.closest('[data-record-attendance]');
				if (!button) return;
				const now = new Date();
				now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
				const today = now.toISOString().slice(0, 10);
				const existingRecord = attendance.find(record =>
					String(record.student_id) === button.dataset.recordAttendance && record.attendance_date === today
				);
				prepareAttendanceForm(button.dataset.recordAttendance, existingRecord || null);
			});

			studentForm.addEventListener('submit', async event => {
				event.preventDefault();
				const data = Object.fromEntries(new FormData(studentForm));
				const editing = data.id !== '';
				try {
					const result = await adminApi('api/students.php', editing ? 'PUT' : 'POST', data);
					resetForm(studentForm, 'student-cancel', 'student-form-title', 'Add a student', 'Save student');
					if (editing) {
						message('Student updated.', true);
					} else if (result.credentials) {
						message(`Student account created. Give these sign-in details to the student now; the password cannot be retrieved later. Email: ${result.credentials.email} · One-time password: ${result.credentials.password}`, true);
					} else {
						message('Student record added and linked to an existing student login; its password was not changed.', true);
					}
					await loadAdminData();
				} catch (error) { message(error.message); }
			});
			document.getElementById('student-cancel').addEventListener('click', () => resetForm(studentForm, 'student-cancel', 'student-form-title', 'Add a student', 'Save student'));
			studentsBody.addEventListener('click', async event => {
				const action = event.target.closest('[data-view-student], [data-student-grades], [data-edit-student], [data-edit-student-login], [data-delete-student], [data-record-attendance], [data-show-activities]');
				if (!action) return;
				const menu = action.closest('details');
				if (menu) menu.open = false;
				const viewId = action.dataset.viewStudent;
				const gradesId = action.dataset.studentGrades;
				const editId = action.dataset.editStudent;
				const editLoginId = action.dataset.editStudentLogin;
				const deleteId = action.dataset.deleteStudent;
				const attendanceId = action.dataset.recordAttendance;
				const activitiesId = action.dataset.showActivities;
				if (viewId) {
					const student = students.find(item => String(item.id) === viewId);
					if (!student) return;
					const details = [
						['Full name', student.full_name],
						['Email', student.email],
						['Student ID', student.id],
						['Roll number', student.student_number || 'Not assigned'],
						['Phone', student.phone || 'Not provided'],
						['Date of birth', student.date_of_birth || 'Not provided'],
						['Gender', formatGender(student.gender)],
						['Class', student.grade_level || 'Not assigned'],
						['Address', student.location || 'Not provided'],
						['Academy', student.academy || 'Not assigned'],
						['Field of study', student.field_of_study || 'Not assigned'],
						['Login account', student.user_id ? 'Linked student login' : 'No login linked']
					];
					document.getElementById('student-detail-list').innerHTML = renderDefinitionList(details);
					document.getElementById('student-dialog').showModal();
				} else if (gradesId) {
					prepareGradeForm(gradesId);
				} else if (editId) {
					const student = students.find(item => String(item.id) === editId);
					if (!student) return;
					studentForm.elements.id.value = student.id;
					for (const key of ['full_name', 'email', 'student_number', 'phone', 'date_of_birth', 'gender', 'location', 'grade_level']) studentForm.elements[key].value = student[key] || '';
					studentForm.elements.academy_id.value = student.academy_id || '';
					updateStudentFields(student.field_of_study_id || '');
					document.getElementById('student-form-title').textContent = 'Edit student';
					document.getElementById('student-cancel').hidden = false;
					studentForm.querySelector('button[type="submit"]').textContent = 'Update student';
					document.getElementById('student-form-title').scrollIntoView({behavior: 'smooth', block: 'center'});
					document.getElementById('student-name').focus({preventScroll: true});
				} else if (editLoginId) {
					loginStudentSelect.value = editLoginId;
					updateLoginEmailFields();
					document.getElementById('student-login-title').scrollIntoView({behavior: 'smooth', block: 'center'});
					loginNewEmail.focus({preventScroll: true});
				} else if (attendanceId) {
					const now = new Date();
					now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
					const today = now.toISOString().slice(0, 10);
					const existingRecord = attendance.find(record =>
						String(record.student_id) === attendanceId && record.attendance_date === today
					);
					prepareAttendanceForm(attendanceId, existingRecord || null);
				} else if (activitiesId) {
					activityStudentFilter.value = activitiesId;
					renderStudentActivities();
					document.getElementById('student-activities').scrollIntoView({behavior: 'smooth', block: 'start'});
				} else if (deleteId && confirm('Remove this student, their login, grades, and attendance records?')) {
					try { await adminApi('api/students.php', 'DELETE', {id: deleteId}); message('Student, login, grades, and attendance records removed.', true); await loadAdminData(); }
					catch (error) { message(error.message); }
				}
			});
			document.getElementById('student-dialog-close').addEventListener('click', () => document.getElementById('student-dialog').close());
			document.getElementById('student-dialog').addEventListener('click', event => {
				if (event.target === event.currentTarget) event.currentTarget.close();
			});
			gradeForm.addEventListener('submit', async event => {
				event.preventDefault();
				const data = Object.fromEntries(new FormData(gradeForm));
				if (data.course_id === 'legacy') {
					data.course_id = '';
					data.subject_id = '';
				}
				const editing = data.id !== '';
				try {
					await adminApi('api/grades.php', editing ? 'PUT' : 'POST', data);
					resetForm(gradeForm, 'grade-cancel', 'grade-form-title', 'Add a grade', 'Save grade');
					document.getElementById('grade-max').value = '100';
					message(editing ? 'Grade updated.' : 'Grade added.', true);
					await loadAdminData();
				} catch (error) { message(error.message); }
			});
			document.getElementById('grade-cancel').addEventListener('click', () => resetForm(gradeForm, 'grade-cancel', 'grade-form-title', 'Add a grade', 'Save grade'));
			gradesBody.addEventListener('click', async event => {
				const action = event.target.closest('[data-edit-grade], [data-delete-grade]');
				if (!action) return;
				const editId = action.dataset.editGrade;
				const deleteId = action.dataset.deleteGrade;
				if (editId) {
					const grade = grades.find(item => String(item.id) === editId);
					if (grade) prepareGradeForm(grade.student_id, grade);
				} else if (deleteId && confirm('Remove this grade?')) {
					try { await adminApi('api/grades.php', 'DELETE', {id: deleteId}); message('Grade removed.', true); await loadAdminData(); }
					catch (error) { message(error.message); }
				}
			});
			activitiesBody.addEventListener('click', async event => {
				const action = event.target.closest('[data-edit-activity], [data-delete-activity]');
				if (!action) return;
				const kind = action.dataset.editActivity || action.dataset.deleteActivity;
				const activityId = action.dataset.activityId;
				if (action.dataset.editActivity) {
					if (kind === 'grade') {
						const grade = grades.find(item => String(item.id) === activityId);
						if (grade) prepareGradeForm(grade.student_id, grade);
					} else {
						const record = attendance.find(item => String(item.id) === activityId);
						if (record) prepareAttendanceForm(record.student_id, record);
					}
					return;
				}
				const activityName = kind === 'grade' ? 'grade' : 'attendance record';
				if (!confirm(`Remove this ${activityName}?`)) return;
				try {
					await adminApi(`api/${kind === 'grade' ? 'grades' : 'attendance'}.php`, 'DELETE', {id: activityId});
					message(`${kind === 'grade' ? 'Grade' : 'Attendance record'} removed.`, true);
					await loadAdminData();
				} catch (error) { message(error.message); }
			});
			attendanceForm.addEventListener('submit', async event => {
				event.preventDefault();
				const data = Object.fromEntries(new FormData(attendanceForm));
				const editing = data.id !== '';
				try {
					await adminApi('api/attendance.php', editing ? 'PUT' : 'POST', data);
					resetForm(attendanceForm, 'attendance-cancel', 'attendance-form-title', 'Record attendance', 'Save attendance');
					message(editing ? 'Attendance updated.' : 'Attendance recorded.', true);
					await loadAdminData();
				} catch (error) { message(error.message); }
			});
			document.getElementById('attendance-cancel').addEventListener('click', () => resetForm(attendanceForm, 'attendance-cancel', 'attendance-form-title', 'Record attendance', 'Save attendance'));
			attendanceBody.addEventListener('click', async event => {
				const action = event.target.closest('[data-edit-attendance], [data-delete-attendance]');
				if (!action) return;
				const editId = action.dataset.editAttendance;
				const deleteId = action.dataset.deleteAttendance;
				if (editId) {
					const record = attendance.find(item => String(item.id) === editId);
					if (record) prepareAttendanceForm(record.student_id, record);
				} else if (deleteId && confirm('Remove this attendance record?')) {
					try { await adminApi('api/attendance.php', 'DELETE', {id: deleteId}); message('Attendance record removed.', true); await loadAdminData(); }
					catch (error) { message(error.message); }
				}
			});
			loadAdminData();
		} else {
			const menu = document.getElementById('student-menu');
			const menuToggle = document.getElementById('student-menu-toggle');
			const menuBackdrop = document.getElementById('student-menu-backdrop');
			const studentViews = Array.from(document.querySelectorAll('.student-view'));
			const viewTitles = {
				overview: ['Student dashboard', 'Review your academic information and attendance.'],
				password: ['Change password', 'Update your student account password.']
			};
			let closeMenuTimer = null;
			function setMenuOpen(open) {
				menuToggle.setAttribute('aria-expanded', String(open));
				menuToggle.setAttribute('aria-label', open ? 'Close student navigation' : 'Open student navigation');
				menu.classList.toggle('is-open', open);
				menu.setAttribute('aria-hidden', String(!open));
				menu.inert = !open;
				if (closeMenuTimer !== null) {
					window.clearTimeout(closeMenuTimer);
					closeMenuTimer = null;
				}
				if (open) {
					menuBackdrop.hidden = false;
					window.requestAnimationFrame(() => menuBackdrop.classList.add('is-visible'));
					const firstMenuItem = menu.querySelector('button');
					if (firstMenuItem) firstMenuItem.focus();
				} else {
					menuBackdrop.classList.remove('is-visible');
					menuToggle.focus();
					closeMenuTimer = window.setTimeout(() => {
						if (menuToggle.getAttribute('aria-expanded') === 'false') menuBackdrop.hidden = true;
					}, 220);
				}
			}
			function showStudentView(viewName) {
				const view = document.getElementById(`student-view-${viewName}`);
				if (!view || !viewTitles[viewName]) return;
				for (const section of studentViews) section.hidden = section !== view;
				for (const button of document.querySelectorAll('[data-student-view], [data-student-section]')) {
					if (button.dataset.studentView === viewName) button.setAttribute('aria-current', 'page');
					else button.removeAttribute('aria-current');
				}
				document.getElementById('student-page-title').textContent = viewTitles[viewName][0];
				document.getElementById('student-page-intro').textContent = viewTitles[viewName][1];
				setMenuOpen(false);
				window.scrollTo({top: 0, behavior: 'smooth'});
			}
			function showStudentSection(sectionId) {
				const section = document.getElementById(sectionId);
				if (!section) return;
				for (const view of studentViews) view.hidden = view.id !== 'student-view-overview';
				for (const button of document.querySelectorAll('[data-student-view], [data-student-section]')) {
					if (button.dataset.studentSection === sectionId) button.setAttribute('aria-current', 'page');
					else button.removeAttribute('aria-current');
				}
				document.getElementById('student-page-title').textContent = viewTitles.overview[0];
				document.getElementById('student-page-intro').textContent = viewTitles.overview[1];
				setMenuOpen(false);
				section.scrollIntoView({behavior: 'smooth', block: 'start'});
			}
			menuToggle.addEventListener('click', () => {
				setMenuOpen(menuToggle.getAttribute('aria-expanded') !== 'true');
			});
			menuBackdrop.addEventListener('click', () => setMenuOpen(false));
			menu.addEventListener('click', event => {
				const button = event.target.closest('[data-student-view], [data-student-section]');
				if (!button) return;
				if (button.dataset.studentSection) showStudentSection(button.dataset.studentSection);
				else showStudentView(button.dataset.studentView);
			});
			document.getElementById('student-header-password').addEventListener('click', () => showStudentView('password'));
			document.addEventListener('keydown', event => {
				if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
					setMenuOpen(false);
					menuToggle.focus();
				}
			});
			const passwordForm = document.getElementById('student-password-form');
			passwordForm.addEventListener('submit', async event => {
				event.preventDefault();
				const data = Object.fromEntries(new FormData(passwordForm));
				try {
					const result = await api('api/student-password.php', 'PUT', data);
					CSRF_TOKEN = result.csrf_token;
					document.querySelector('meta[name="csrf-token"]').content = CSRF_TOKEN;
					document.querySelector('form[action="logout.php"] input[name="csrf_token"]').value = CSRF_TOKEN;
					passwordForm.reset();
					message('Your password was changed successfully.', true);
				} catch (error) {
					message(error.message);
				}
			});

			async function loadStudentData() {
				try {
					const [studentResult, gradeResult, attendanceResult, reportResult] = await Promise.all([
						api('api/students.php'), api('api/grades.php'), api('api/attendance.php'), api('api/reports.php')
					]);
					const details = document.getElementById('student-details');
					if (studentResult.student) {
						const student = studentResult.student;
						const fields = [
							['Student ID', student.id],
							['Roll number', student.student_number || 'Not assigned'],
							['Full name', student.full_name],
							['Email', student.email],
							['Phone', student.phone || 'Not provided'],
							['Date of birth', student.date_of_birth || 'Not provided'],
							['Class', student.grade_level || 'Not assigned'],
							['Gender', formatGender(student.gender)],
							['Address', student.location || 'Not provided'],
							['Academy', student.academy || 'Not assigned'],
							['Field of study', student.field_of_study || 'Not assigned']
						];
						details.innerHTML = renderDefinitionList(fields);
					} else {
						details.innerHTML = '<div><dt>Student record</dt><dd>No student record is linked to this account. Please contact an administrator.</dd></div>';
					}
					const body = document.getElementById('grades-body');
					body.innerHTML = gradeResult.grades.length ? gradeResult.grades.map(grade => {
						const percentage = Number(grade.score) / Number(grade.max_score) * 100;
						return `<tr><td>${escapeHtml(grade.course_academy || '—')}</td><td>${escapeHtml(grade.course_name || '—')}</td><td>${escapeHtml(grade.subject)}</td><td>${escapeHtml(grade.term)}</td><td>${escapeHtml(grade.score)}</td><td>${escapeHtml(grade.max_score)}</td><td>${percentage.toFixed(2)}%</td></tr>`;
					}).join('') : '<tr><td class="empty" colspan="7">No grades are available yet.</td></tr>';
					const report = reportResult.report[0] || null;
					const attendancePercentage = report && report.attendance_percentage !== null
						? `${report.attendance_percentage}%` : '—';
					document.getElementById('student-attendance-overall').textContent = `Overall: ${attendancePercentage}`;
					const attendanceSummaryBody = document.getElementById('attendance-summary-body');
					const attendanceByTerm = new Map();
					for (const record of attendanceResult.attendance) {
						const term = record.term || 'Term not specified';
						if (!attendanceByTerm.has(term)) attendanceByTerm.set(term, {term, present: 0, sessions: 0});
						const summary = attendanceByTerm.get(term);
						summary.sessions += 1;
						if (record.status === 'present') summary.present += 1;
					}
					attendanceSummaryBody.innerHTML = attendanceByTerm.size
						? Array.from(attendanceByTerm.values()).map(summary => {
							const percentage = (summary.present / summary.sessions * 100).toFixed(2);
							return `<tr><td>${escapeHtml(studentResult.student?.grade_level || 'Class not assigned')}</td><td>${escapeHtml(summary.term)}</td><td>${summary.present} / ${summary.sessions}</td><td>${percentage}%</td></tr>`;
						}).join('')
						: '<tr><td class="empty" colspan="4">No attendance information is available yet.</td></tr>';
					document.getElementById('student-grade-average').textContent = `Average: ${report && report.average_percentage !== null ? `${report.average_percentage}%` : '—'}`;

					const courseGroups = new Map();
					for (const grade of gradeResult.grades) {
						const courseName = grade.course_name || 'Other grades';
						const key = JSON.stringify([courseName, grade.term]);
						if (!courseGroups.has(key)) {
							courseGroups.set(key, {
								courseName,
								term: grade.term,
								academy: grade.course_academy || '—',
								teacher: grade.teacher_name || 'Not assigned',
								subjects: new Set()
							});
						}
						courseGroups.get(key).subjects.add(grade.subject);
					}
					const studentClass = studentResult.student?.grade_level || 'Not assigned';
					document.getElementById('student-courses').innerHTML = courseGroups.size
						? Array.from(courseGroups.values()).map(course => `<article class="student-course-card">
							<h3>${escapeHtml(course.courseName)}</h3>
							<p><span>Class</span><strong>${escapeHtml(studentClass)}</strong></p>
							<p><span>Semester / term</span><strong>${escapeHtml(course.term)}</strong></p>
							<p><span>Academy</span><strong>${escapeHtml(course.academy)}</strong></p>
							<p><span>Teacher</span><strong>${escapeHtml(course.teacher)}</strong></p>
							<p><span>Subjects</span><strong>${escapeHtml(Array.from(course.subjects).join(', '))}</strong></p>
						</article>`).join('')
						: '<p class="empty">No course records are available yet.</p>';
					const attendanceBody = document.getElementById('attendance-body');
					attendanceBody.innerHTML = attendanceResult.attendance.length ? attendanceResult.attendance.map(record =>
						`<tr><td>${escapeHtml(record.attendance_date)}</td><td>${escapeHtml(record.term || '—')}</td><td>${escapeHtml(record.status.replace(/^\w/, letter => letter.toUpperCase()))}</td><td>${escapeHtml(record.note || '—')}</td></tr>`
					).join('') : '<tr><td class="empty" colspan="4">No attendance records are available yet.</td></tr>';
				} catch (error) { message(error.message); }
			}
			loadStudentData();
		}
	</script>
</body>
</html>
