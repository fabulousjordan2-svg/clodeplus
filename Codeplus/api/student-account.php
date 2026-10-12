<?php
declare(strict_types=1);

require __DIR__ . '/common.php';

$user = api_user(true);
api_require_method(['PUT']);
api_require_admin_reauth();
api_require_csrf();
$input = api_input();
$studentId = api_id($input['student_id'] ?? null);
$email = strtolower(api_text($input['email'] ?? null));
$newPassword = $input['new_password'] ?? '';
$passwordConfirmation = $input['password_confirmation'] ?? '';
$generatePassword = $input['generate_password'] ?? false;

if ($studentId === null || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
	|| hash_equals(strtolower(ADMIN_EMAIL), $email)) {
	api_response(['error' => 'Choose a student and enter a valid, non-administrator login email.'], 422);
}
if (!is_bool($generatePassword) || !is_string($newPassword) || !is_string($passwordConfirmation)
	|| ($newPassword !== '' && (strlen($newPassword) < 8 || strlen($newPassword) > 4096))
	|| $newPassword !== $passwordConfirmation
	|| ($generatePassword && ($newPassword !== '' || $passwordConfirmation !== ''))) {
	api_response(['error' => 'Enter matching replacement passwords of at least 8 characters, or leave both blank to keep the current password.'], 422);
}

$connection = null;
try {
	$connection = database();
	$connection->beginTransaction();
	$lookup = $connection->prepare(
		'SELECT user_id FROM students WHERE id = :id FOR UPDATE'
	);
	$lookup->execute(['id' => $studentId]);
	$student = $lookup->fetch();
	if (!$student) {
		$connection->rollBack();
		api_response(['error' => 'Student not found.'], 404);
	}
	if ($student['user_id'] === null) {
		$connection->rollBack();
		api_response(['error' => 'This student does not have a linked login account yet.'], 409);
	}

	$accountLookup = $connection->prepare(
		'SELECT id FROM users WHERE id = :id AND role = \'student\' FOR UPDATE'
	);
	$accountLookup->execute(['id' => $student['user_id']]);
	if (!$accountLookup->fetch()) {
		$connection->rollBack();
		api_response(['error' => 'The linked student login account could not be found.'], 409);
	}

	$collision = $connection->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
	$collision->execute(['email' => $email, 'id' => $student['user_id']]);
	if ($collision->fetch()) {
		$connection->rollBack();
		api_response(['error' => 'That login email is already used by another account.'], 409);
	}

	$passwordToSet = $generatePassword
		? rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=')
		: $newPassword;
	$updateProfile = $connection->prepare('UPDATE students SET email = :email WHERE id = :id');
	$updateProfile->execute(['email' => $email, 'id' => $studentId]);
	if ($passwordToSet === '') {
		$updateAccount = $connection->prepare('UPDATE users SET email = :email WHERE id = :id AND role = \'student\'');
		$updateAccount->execute(['email' => $email, 'id' => $student['user_id']]);
	} else {
		$updateAccount = $connection->prepare(
			'UPDATE users SET email = :email, password_hash = :password_hash
			WHERE id = :id AND role = \'student\''
		);
		$updateAccount->execute([
			'email' => $email,
			'password_hash' => password_hash($passwordToSet, PASSWORD_DEFAULT),
			'id' => $student['user_id'],
		]);
	}
	$connection->commit();

	api_response([
		'message' => $passwordToSet === ''
			? 'Student login email updated. The existing password was unchanged.'
			: ($generatePassword
				? 'Student login email updated and a one-time password generated. Share it securely; the student can change it after signing in.'
				: 'Student login email and password updated. The password is not viewable.'),
		'password_changed' => $passwordToSet !== '',
		'temporary_password' => $generatePassword ? $passwordToSet : null,
	]);
} catch (PDOException $exception) {
	if ($connection instanceof PDO && $connection->inTransaction()) {
		$connection->rollBack();
	}
	api_database_error($exception);
}
