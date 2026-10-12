<?php
declare(strict_types=1);

require __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: Index.html');
	exit;
}

$expectedRole = $_POST['expected_role'] ?? 'student';
if (!in_array($expectedRole, ['admin', 'student'], true)) {
	header('Location: Index.html?error=role');
	exit;
}

if (!valid_csrf_token()) {
	header('Location: ' . ($expectedRole === 'admin' ? 'admin-login.php?error=csrf' : 'Index.html?error=csrf'));
	exit;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if ($expectedRole === 'admin' && !hash_equals(strtolower(ADMIN_EMAIL), $email)) {
	header('Location: admin-login.php?error=invalid');
	exit;
}

try {
	$statement = database()->prepare('SELECT id, name, password_hash, role FROM users WHERE email = :email LIMIT 1');
	$statement->execute(['email' => $email]);
	$user = $statement->fetch();
} catch (PDOException $exception) {
	error_log($exception->getMessage());
	header('Location: ' . ($expectedRole === 'admin' ? 'admin-login.php?error=unavailable' : 'Index.html?error=unavailable'));
	exit;
}

if (!$user || !password_verify($password, $user['password_hash'])) {
	header('Location: ' . ($expectedRole === 'admin' ? 'admin-login.php?error=invalid' : 'Index.html?error=invalid'));
	exit;
}

if ($user['role'] !== $expectedRole) {
	header('Location: ' . ($expectedRole === 'admin' ? 'admin-login.php?error=role' : 'Index.html?error=role'));
	exit;
}

if ($expectedRole === 'student') {
	try {
		$studentAccount = database()->prepare('SELECT id FROM students WHERE user_id = :user_id LIMIT 1');
		$studentAccount->execute(['user_id' => $user['id']]);
		if (!$studentAccount->fetch()) {
			header('Location: Index.html?error=invalid');
			exit;
		}
	} catch (PDOException $exception) {
		error_log($exception->getMessage());
		header('Location: Index.html?error=unavailable');
		exit;
	}
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
header('Location: dashboard.php');
exit;