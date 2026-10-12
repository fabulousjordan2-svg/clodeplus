<?php
declare(strict_types=1);

const ADMIN_EMAIL = 'Admin@codeplus.local';

if (session_status() !== PHP_SESSION_ACTIVE) {
	ini_set('session.use_strict_mode', '1');
	session_set_cookie_params([
		'httponly' => true,
		'samesite' => 'Lax',
		'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
	]);

	session_start();
}

function database(): \PDO
{
	static $connection = null;

	if ($connection instanceof \PDO) {
		return $connection;
	}

	$host = getenv('DB_HOST') ?: '127.0.0.1';
	$name = getenv('DB_NAME') ?: 'codeplus';
	$user = getenv('DB_USER') ?: 'root';
	$password = getenv('DB_PASSWORD') ?: '';
	$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
	$connection = new \PDO($dsn, $user, $password, [
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
		\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
		\PDO::ATTR_EMULATE_PREPARES => false,
	]);

	return $connection;
}

function escape(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
	if (!isset($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}

	return $_SESSION['csrf_token'];
}

function valid_csrf_token(): bool
{
	$submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
	$sessionToken = $_SESSION['csrf_token'] ?? '';

	return is_string($submittedToken)
		&& is_string($sessionToken)
		&& $sessionToken !== ''
		&& hash_equals($sessionToken, $submittedToken);
}