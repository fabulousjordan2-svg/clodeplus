<?php
declare(strict_types=1);

require __DIR__ . '/database.php';

if (isset($_SESSION['user_id'])) {
	header('Location: dashboard.php');
	exit;
}

$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#f3f6f2">
	<meta name="csrf-token" content="<?= escape($csrfToken) ?>">
	<title>Administrator sign in | Codeplus</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
	<style>
		:root { color-scheme: light; --paper: #f3f6f2; --white: #fff; --ink: #202823; --muted: #777e78; --line: #e2e9e1; --green: #28604b; --dark: #1d4838; --red: #a44936; --serif: "Fraunces", Georgia, serif; --sans: "DM Sans", "Segoe UI", sans-serif; }
		* { box-sizing: border-box; }
		body { display: grid; min-height: 100vh; margin: 0; place-items: center; padding: 24px; background: radial-gradient(ellipse at 12% 0%, #d7eadc 0, transparent 34rem), var(--paper); color: var(--ink); font-family: var(--sans); }
		main { width: min(100%, 440px); padding: clamp(26px, 7vw, 48px); border: 1px solid var(--line); border-radius: 18px; background: var(--white); box-shadow: 0 24px 64px rgb(31 62 46 / 10%); }
		.brand { display: inline-flex; align-items: center; gap: 10px; color: var(--dark); font-size: 18px; font-weight: 700; text-decoration: none; }
		.brand-mark { display: grid; width: 34px; height: 34px; place-items: center; border: 1px solid var(--green); border-radius: 50%; font-family: var(--serif); font-size: 21px; }
		.eyebrow { margin: 34px 0 10px; color: var(--green); font-size: 11px; font-weight: 700; letter-spacing: 1.3px; text-transform: uppercase; }
		h1 { margin: 0; font-family: var(--serif); font-size: clamp(34px, 8vw, 44px); font-weight: 500; letter-spacing: -1px; }
		.intro { margin: 12px 0 28px; color: var(--muted); font-size: 14px; line-height: 1.65; }
		.field { margin-top: 17px; }
		label { display: block; margin-bottom: 7px; font-size: 13px; font-weight: 600; }
		input { width: 100%; min-height: 48px; padding: 11px 13px; border: 1px solid var(--line); border-radius: 8px; font: inherit; }
		input:focus { outline: 3px solid rgb(40 96 75 / 13%); border-color: var(--green); }
		button { width: 100%; min-height: 48px; margin-top: 22px; border: 0; border-radius: 8px; background: var(--green); color: var(--white); cursor: pointer; font: inherit; font-weight: 700; }
		button:hover { background: var(--dark); }
		.status { min-height: 22px; margin: 13px 0 0; color: var(--red); font-size: 13px; line-height: 1.5; }
		.links { display: grid; gap: 10px; margin-top: 14px; text-align: center; }
		a { color: var(--green); font-size: 13px; font-weight: 600; text-underline-offset: 3px; }
	</style>
</head>
<body>
	<main>
		<a class="brand" href="Index.html"><span class="brand-mark" aria-hidden="true">m</span><span>codeplus</span></a>
		<p class="eyebrow">Staff access</p>
		<h1>Administrator sign in</h1>
		<p class="intro">Use your administrator account to manage student records, grades, attendance, and reports.</p>
		<form action="login.php" method="post">
			<input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
			<input type="hidden" name="expected_role" value="admin">
			<div class="field">
				<label for="email">Administrator email</label>
				<input id="email" name="email" type="email" value="<?= escape(ADMIN_EMAIL) ?>" autocomplete="username" readonly required>
			</div>
			<div class="field">
				<label for="password">Password</label>
				<input id="password" name="password" type="password" autocomplete="current-password" required>
			</div>
			<button type="submit">Sign in to admin dashboard</button>
			<p class="status" id="form-status" role="status" aria-live="polite"></p>
		</form>
		<nav class="links" aria-label="Other sign-in options">
			<a href="Index.html">Student sign in</a>
		</nav>
		<p class="intro">Forgot the administrator password? Ask the server operator to run <code>php create_demo_admin.php</code> from the project folder. If this address is assigned to a student account, that account must first be moved to a different email.</p>
	</main>
	<script>
		const messages = {
			invalid: 'Those sign-in details did not match an administrator account.',
			role: 'This account is not an administrator. Use the student sign-in page instead.',
			csrf: 'Your sign-in session expired. Refresh this page and try again.',
			unavailable: 'Sign-in is temporarily unavailable. Please try again later.'
		};
		const error = new URLSearchParams(window.location.search).get('error');
		document.getElementById('form-status').textContent = messages[error] || '';
	</script>
</body>
</html>
