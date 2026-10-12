<?php
declare(strict_types=1);

require __DIR__ . '/common.php';

$user = api_user(true);
api_require_method(['POST']);
api_require_csrf();
$input = api_input();
$email = strtolower(api_text($input['email'] ?? null));
$password = $input['password'] ?? null;

$now = time();
$attempts = $_SESSION['admin_reauth_attempts'] ?? null;
if (!is_array($attempts) || !isset($attempts['started_at'], $attempts['count'])
	|| !is_int($attempts['started_at']) || !is_int($attempts['count'])
	|| $now - $attempts['started_at'] >= 900 || $attempts['started_at'] > $now) {
	$attempts = ['started_at' => $now, 'count' => 0];
}
if ($attempts['count'] >= 5) {
	api_response(['error' => 'Too many confirmation attempts. Try again in 15 minutes.'], 429);
}

if (!is_string($password) || strlen($password) > 4096 || $email === ''
	|| !hash_equals(strtolower(ADMIN_EMAIL), $email)
	|| !hash_equals(strtolower(ADMIN_EMAIL), strtolower($user['email']))) {
	$validPassword = false;
} else {
	try {
		$statement = database()->prepare(
			'SELECT password_hash FROM users WHERE id = :id AND email = :email AND role = \'admin\' LIMIT 1'
		);
		$statement->execute(['id' => $user['id'], 'email' => ADMIN_EMAIL]);
		$account = $statement->fetch();
		$validPassword = $account && password_verify($password, $account['password_hash']);
	} catch (PDOException $exception) {
		api_database_error($exception);
	}
}

if (!$validPassword) {
	$attempts['count']++;
	$_SESSION['admin_reauth_attempts'] = $attempts;
	api_response(['error' => 'The administrator email or password is incorrect.'], 403);
}

unset($_SESSION['admin_reauth_attempts']);
$_SESSION['admin_reauthenticated_at'] = $now;
api_response(['message' => 'Administrator access confirmed.']);
