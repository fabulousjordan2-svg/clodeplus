<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

require __DIR__ . '/database.php';

$email = ADMIN_EMAIL;
$name = 'Administrator';

try {
	$connection = database();
	$existing = $connection->prepare('SELECT id, role FROM users WHERE email = :email LIMIT 1');
	$existing->execute(['email' => $email]);
	$account = $existing->fetch();
	if ($account && $account['role'] !== 'admin') {
		fwrite(STDERR, "The reserved administrator email belongs to a non-admin account; no changes were made.\n");
		exit(1);
	}

	$password = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
	if ($account) {
		$save = $connection->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id AND role = \'admin\'');
		$save->execute(['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $account['id']]);
	} else {
		$save = $connection->prepare(
			'INSERT INTO users (name, email, password_hash, role)
			VALUES (:name, :email, :password_hash, \'admin\')'
		);
		$save->execute([
			'name' => $name,
			'email' => $email,
			'password_hash' => password_hash($password, PASSWORD_DEFAULT),
		]);
	}

	fwrite(STDOUT, ($account ? "Administrator password reset.\n" : "Administrator account created.\n")
		. "Email: {$email}\nPassword (shown once): {$password}\n");
} catch (PDOException $exception) {
	error_log($exception->getMessage());
	fwrite(STDERR, "Could not create or reset the administrator. Check the database configuration and schema.\n");
	exit(1);
}
