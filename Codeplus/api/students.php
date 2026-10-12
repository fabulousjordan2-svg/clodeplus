<?php
declare(strict_types=1);

require __DIR__ . '/common.php';

$user = api_user();
$method = $_SERVER['REQUEST_METHOD'];
api_require_method(['GET', 'POST', 'PUT', 'DELETE']);

function student_record_input(array $input): array
{
	$name = api_text($input['full_name'] ?? null);
	$email = strtolower(api_text($input['email'] ?? null));
	$number = api_text($input['student_number'] ?? null);
	$phone = api_text($input['phone'] ?? null);
	$birthDateValue = api_text($input['date_of_birth'] ?? null);
	$birthDate = $birthDateValue === '' ? null : $birthDateValue;
	$gender = strtolower(api_text($input['gender'] ?? null));
	$location = api_text($input['location'] ?? null);
	$level = api_text($input['grade_level'] ?? null);
	$fieldValue = api_text($input['field_of_study_id'] ?? null);
	$fieldId = $fieldValue === '' ? null : api_id($fieldValue);
	$academyValue = api_text($input['academy_id'] ?? null);
	$academyId = $academyValue === '' ? null : api_id($academyValue);
	$parsedDate = $birthDate === null ? null : DateTime::createFromFormat('!Y-m-d', $birthDate);
	$validBirthDate = $birthDate === null
		|| $parsedDate !== false && $parsedDate->format('Y-m-d') === $birthDate && $birthDate <= date('Y-m-d');

	if (hash_equals(strtolower(ADMIN_EMAIL), $email)) {
		api_response(['error' => 'That email is reserved for the administrator account.'], 422);
	}
	if (($fieldValue !== '' && $fieldId === null) || ($academyValue !== '' && $academyId === null)
		|| $name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
		|| strlen($number) > 40 || strlen($phone) > 40 || !$validBirthDate
		|| !in_array($gender, ['', 'male', 'female', 'other', 'prefer_not_to_say'], true)
		|| strlen($location) > 160 || strlen($level) > 40) {
		api_response(['error' => 'Enter valid student details. Check the name, email, phone, date of birth, gender, address, and class fields.'], 422);
	}

	return [
		'full_name' => $name,
		'email' => $email,
		'student_number' => $number !== '' ? $number : null,
		'phone' => $phone,
		'date_of_birth' => $birthDate,
		'gender' => $gender,
		'location' => $location,
		'grade_level' => $level,
		'field_of_study_id' => $fieldId,
		'field_of_study_value' => $fieldValue,
		'academy_id' => $academyId,
		'academy_value' => $academyValue,
	];
}

if ($method === 'GET') {
	try {
		if ($user['role'] === 'admin') {
			$statement = database()->query(
				'SELECT s.id, s.user_id, s.academy_id, a.name AS academy, s.field_of_study_id, f.name AS field_of_study, s.student_number,
				s.full_name, s.email, s.phone, s.date_of_birth, s.gender, s.location, s.grade_level, s.created_at, s.updated_at
				FROM students AS s LEFT JOIN fields_of_study AS f ON f.id = s.field_of_study_id
				LEFT JOIN academies AS a ON a.id = s.academy_id
				ORDER BY s.full_name, s.id'
			);
			api_response(['students' => $statement->fetchAll()]);
		}
		$statement = database()->prepare(
			'SELECT s.id, s.academy_id, a.name AS academy, s.field_of_study_id, f.name AS field_of_study, s.student_number,
			s.full_name, s.email, s.phone, s.date_of_birth, s.gender, s.location, s.grade_level, s.created_at, s.updated_at
			FROM students AS s LEFT JOIN fields_of_study AS f ON f.id = s.field_of_study_id
			LEFT JOIN academies AS a ON a.id = s.academy_id
			WHERE s.user_id = :user_id LIMIT 1'
		);
		$statement->execute(['user_id' => $user['id']]);
		$student = $statement->fetch();
		api_response(['student' => $student ?: null]);
	} catch (PDOException $exception) {
		api_database_error($exception);
	}
}

if ($user['role'] !== 'admin') {
	api_response(['error' => 'Administrator access is required.'], 403);
}
api_require_admin_reauth();
api_require_csrf();
$input = api_input();

if ($method === 'POST') {
	$record = student_record_input($input);
	$name = $record['full_name'];
	$email = $record['email'];
	$fieldId = $record['field_of_study_id'];
	$fieldValue = $record['field_of_study_value'];
	$academyId = $record['academy_id'];
	$academyValue = $record['academy_value'];

	try {
		$connection = database();
		if ($fieldId !== null) {
			$field = $connection->prepare('SELECT academy_id FROM fields_of_study WHERE id = :id');
			$field->execute(['id' => $fieldId]);
			$fieldAcademyId = $field->fetchColumn();
			if ($fieldAcademyId === false || $academyId !== null && (int) $fieldAcademyId !== $academyId) {
				api_response(['error' => 'Choose a field of study that belongs to the selected academy.'], 422);
			}
			$academyId = (int) $fieldAcademyId;
		}
		if ($academyId !== null) {
			$academy = $connection->prepare('SELECT id FROM academies WHERE id = :id');
			$academy->execute(['id' => $academyId]);
			if (!$academy->fetch()) {
				api_response(['error' => 'The selected academy does not exist.'], 422);
			}
		}
		$record['academy_id'] = $academyId;
		$record['field_of_study_id'] = $fieldId;
		$connection->beginTransaction();
		$account = $connection->prepare('SELECT id, role FROM users WHERE email = :email LIMIT 1 FOR UPDATE');
		$account->execute(['email' => $email]);
		$existingAccount = $account->fetch();
		if ($existingAccount && $existingAccount['role'] !== 'student') {
			$connection->rollBack();
			api_response(['error' => 'That email belongs to an administrator account.'], 409);
		}

		$existingStudent = $connection->prepare('SELECT id, user_id FROM students WHERE email = :email LIMIT 1 FOR UPDATE');
		$existingStudent->execute(['email' => $email]);
		$student = $existingStudent->fetch();
		if ($student && $student['user_id'] !== null) {
			$connection->rollBack();
			api_response(['error' => 'A student record already exists for that email.'], 409);
		}

		if ($existingAccount) {
			$linkedStudent = $connection->prepare('SELECT id FROM students WHERE user_id = :user_id LIMIT 1 FOR UPDATE');
			$linkedStudent->execute(['user_id' => $existingAccount['id']]);
			if ($linkedStudent->fetch()) {
				$connection->rollBack();
				api_response(['error' => 'That student login is already linked to another student record.'], 409);
			}
		}

		$password = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
		if ($existingAccount) {
			$userId = (int) $existingAccount['id'];
			$updateAccount = $connection->prepare(
				'UPDATE users SET name = :name, password_hash = :password_hash
				WHERE id = :id AND role = \'student\''
			);
			$updateAccount->execute([
				'name' => $name,
				'password_hash' => password_hash($password, PASSWORD_DEFAULT),
				'id' => $userId,
			]);
		} else {
			$createAccount = $connection->prepare(
				'INSERT INTO users (name, email, password_hash, role)
				VALUES (:name, :email, :password_hash, \'student\')'
			);
			$createAccount->execute([
				'name' => $name,
				'email' => $email,
				'password_hash' => password_hash($password, PASSWORD_DEFAULT),
			]);
			$userId = (int) $connection->lastInsertId();
		}

		$params = $record + [
			'user_id' => $userId,
		];
		unset($params['field_of_study_value'], $params['academy_value']);
		if ($student) {
			$statement = $connection->prepare(
				'UPDATE students SET user_id = :user_id, academy_id = :academy_id, field_of_study_id = :field_of_study_id, student_number = :student_number,
				full_name = :full_name, email = :email, phone = :phone, date_of_birth = :date_of_birth, gender = :gender,
				location = :location, grade_level = :grade_level WHERE id = :id'
			);
			$params['id'] = (int) $student['id'];
			$statement->execute($params);
			$studentId = (int) $student['id'];
		} else {
			$statement = $connection->prepare(
				'INSERT INTO students (user_id, academy_id, field_of_study_id, student_number, full_name, email, phone, date_of_birth, gender, location, grade_level)
				VALUES (:user_id, :academy_id, :field_of_study_id, :student_number, :full_name, :email, :phone, :date_of_birth, :gender, :location, :grade_level)'
			);
			$statement->execute($params);
			$studentId = (int) $connection->lastInsertId();
		}
		$connection->commit();

		$response = ['student' => ['id' => $studentId]];
		$response['credentials'] = ['email' => $email, 'password' => $password];
		api_response($response, 201);
	} catch (PDOException $exception) {
		if (isset($connection) && $connection->inTransaction()) {
			$connection->rollBack();
		}
		api_database_error($exception);
	}
}

$studentId = api_id($input['id'] ?? null);
if ($studentId === null) {
	api_response(['error' => 'A valid student id is required.'], 422);
}

if ($method === 'PUT') {
	$record = student_record_input($input);
	$name = $record['full_name'];
	$email = $record['email'];
	$fieldId = $record['field_of_study_id'];
	$fieldValue = $record['field_of_study_value'];
	$academyId = $record['academy_id'];
	$academyValue = $record['academy_value'];

	$connection = database();
	try {
		$connection->beginTransaction();
		$lookup = $connection->prepare('SELECT user_id FROM students WHERE id = :id FOR UPDATE');
		$lookup->execute(['id' => $studentId]);
		$current = $lookup->fetch();
		if (!$current) {
			$connection->rollBack();
			api_response(['error' => 'Student not found.'], 404);
		}
		if ($fieldId !== null) {
			$field = $connection->prepare('SELECT academy_id FROM fields_of_study WHERE id = :id');
			$field->execute(['id' => $fieldId]);
			$fieldAcademyId = $field->fetchColumn();
			if ($fieldAcademyId === false || $academyId !== null && (int) $fieldAcademyId !== $academyId) {
				$connection->rollBack();
				api_response(['error' => 'Choose a field of study that belongs to the selected academy.'], 422);
			}
			$academyId = (int) $fieldAcademyId;
		}
		if ($academyId !== null) {
			$academy = $connection->prepare('SELECT id FROM academies WHERE id = :id');
			$academy->execute(['id' => $academyId]);
			if (!$academy->fetch()) {
				$connection->rollBack();
				api_response(['error' => 'The selected academy does not exist.'], 422);
			}
		}
		$record['academy_id'] = $academyId;
		$record['field_of_study_id'] = $fieldId;
		$statement = $connection->prepare(
			'UPDATE students SET academy_id = :academy_id, field_of_study_id = :field_of_study_id, student_number = :student_number,
			full_name = :full_name, email = :email, phone = :phone, date_of_birth = :date_of_birth, gender = :gender,
			location = :location, grade_level = :grade_level WHERE id = :id'
		);
		$record['id'] = $studentId;
		unset($record['field_of_study_value'], $record['academy_value']);
		$statement->execute($record);
		if ($current['user_id'] !== null) {
			$account = $connection->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
			$account->execute(['name' => $name, 'email' => $email, 'id' => $current['user_id']]);
		}
		$connection->commit();
		api_response(['message' => 'Student updated.']);
	} catch (PDOException $exception) {
		if ($connection->inTransaction()) {
			$connection->rollBack();
		}
		api_database_error($exception);
	}
}

if ($method === 'DELETE') {
	$connection = database();
	try {
		$connection->beginTransaction();
		$lookup = $connection->prepare('SELECT user_id FROM students WHERE id = :id FOR UPDATE');
		$lookup->execute(['id' => $studentId]);
		$current = $lookup->fetch();
		if (!$current) {
			$connection->rollBack();
			api_response(['error' => 'Student not found.'], 404);
		}
		$delete = $connection->prepare('DELETE FROM students WHERE id = :id');
		$delete->execute(['id' => $studentId]);
		if ($current['user_id'] !== null) {
			$account = $connection->prepare('DELETE FROM users WHERE id = :id AND role = \'student\'');
			$account->execute(['id' => $current['user_id']]);
		}
		$connection->commit();
		api_response(['message' => 'Student and associated grades removed.']);
	} catch (PDOException $exception) {
		if ($connection->inTransaction()) {
			$connection->rollBack();
		}
		api_database_error($exception);
	}
}
