<?php
declare(strict_types=1);

require __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !valid_csrf_token()) {
	header('Location: dashboard.php');
	exit;
}

$loginPage = ($_SESSION['user_role'] ?? '') === 'admin' ? 'admin-login.php' : 'Index.html';
$_SESSION = [];

if (ini_get('session.use_cookies')) {
	$cookie = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
}

session_destroy();
header('Location: ' . $loginPage);
exit;