<?php
declare(strict_types=1);

require __DIR__ . '/common.php';

$user = api_user(true);
api_require_method(['PUT']);
api_require_csrf();
$input = api_input();
$currentPassword = $input['current_password'] ?? null;
$newPassword = $input['new_password'] ?? null;
$confirmation = $input['password_confirmation'] ?? null;

if (!is_string($currentPassword) || !is_string($newPassword) || !is_string($confirmation)
	|| strlen($currentPassword) > 4096 || strlen($newPassword) < 8 || strlen($newPassword) > 4096
	|| $newPassword !== $confirmation) {
	api_response(['error' => 'Enter your current password and a matching new password of at least 8 characters.'], 422);
}

$connection = null;
try {
	$connection = database();
	$connection->beginTransaction();
	$account = $connection->prepare(
		'SELECT password_hash FROM users
		WHERE id = :id AND email = :email AND role = \'admin\' FOR UPDATE'
	);
	$account->execute(['id' => $user['id'], 'email' => ADMIN_EMAIL]);
	$currentAccount = $account->fetch();
	if (!$currentAccount || !password_verify($currentPassword, $currentAccount['password_hash'])) {
		$connection->rollBack();
		api_response(['error' => 'Your current password is incorrect.'], 403);
	}

	$update = $connection->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id AND role = \'admin\'');
	$update->execute([
		'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
		'id' => $user['id'],
	]);
	$connection->commit();

	unset($_SESSION['admin_reauthenticated_at']);
	session_regenerate_id(true);
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	api_response([
		'message' => 'Password changed.',
		'csrf_token' => $_SESSION['csrf_token'],
	]);
} catch (PDOException $exception) {
	if ($connection instanceof PDO && $connection->inTransaction()) {
		$connection->rollBack();
	}
	api_database_error($exception);
}
