<?php
declare(strict_types=1);

require __DIR__ . '/common.php';

$user = api_user();
$method = $_SERVER['REQUEST_METHOD'];
api_require_method(['GET', 'POST', 'PUT', 'DELETE']);

if ($method === 'GET') {
	try {
		if ($user['role'] === 'admin') {
			$statement = database()->query(
				'SELECT a.id, a.student_id, s.full_name AS student_name, a.attendance_date, a.term, a.status, a.note, a.updated_at
				FROM attendance AS a JOIN students AS s ON s.id = a.student_id
				ORDER BY a.attendance_date DESC, s.full_name'
			);
		} else {
			$statement = database()->prepare(
				'SELECT a.id, a.student_id, s.full_name AS student_name, a.attendance_date, a.term, a.status, a.note, a.updated_at
				FROM attendance AS a JOIN students AS s ON s.id = a.student_id
				WHERE s.user_id = :user_id ORDER BY a.attendance_date DESC'
			);
			$statement->execute(['user_id' => $user['id']]);
		}
		api_response(['attendance' => $statement->fetchAll()]);
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

if ($method === 'POST' || $method === 'PUT') {
	$attendanceId = $method === 'PUT' ? api_id($input['id'] ?? null) : null;
	$studentId = api_id($input['student_id'] ?? null);
	$date = api_text($input['attendance_date'] ?? null);
	$term = api_text($input['term'] ?? null);
	$status = api_text($input['status'] ?? null);
	$note = api_text($input['note'] ?? null);
	$parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
	$validDate = $parsedDate !== false && $parsedDate->format('Y-m-d') === $date;

	if ($method === 'PUT' && $attendanceId === null || $studentId === null || !$validDate || $term === '' || strlen($term) > 80
		|| !in_array($status, ['present', 'absent', 'late', 'excused'], true) || strlen($note) > 500) {
		api_response(['error' => 'Provide a valid student, date, semester or term, attendance status, and note (up to 500 characters).'], 422);
	}

	try {
		$connection = database();
		if ($method === 'POST') {
			$statement = $connection->prepare(
				'INSERT INTO attendance (student_id, attendance_date, term, status, note)
				VALUES (:student_id, :attendance_date, :term, :status, :note)'
			);
		} else {
			$statement = $connection->prepare(
				'UPDATE attendance SET student_id = :student_id, attendance_date = :attendance_date,
				term = :term, status = :status, note = :note WHERE id = :id'
			);
		}
		$params = [
			'student_id' => $studentId,
			'attendance_date' => $date,
			'term' => $term,
			'status' => $status,
			'note' => $note,
		];
		if ($method === 'PUT') {
			$params['id'] = $attendanceId;
		}
		$statement->execute($params);
		if ($method === 'PUT' && $statement->rowCount() === 0) {
			$exists = $connection->prepare('SELECT id FROM attendance WHERE id = :id');
			$exists->execute(['id' => $attendanceId]);
			if (!$exists->fetch()) {
				api_response(['error' => 'Attendance record not found.'], 404);
			}
		}
		api_response(
			['message' => $method === 'POST' ? 'Attendance recorded.' : 'Attendance updated.'],
			$method === 'POST' ? 201 : 200
		);
	} catch (PDOException $exception) {
		api_database_error($exception);
	}
}

$attendanceId = api_id($input['id'] ?? null);
if ($attendanceId === null) {
	api_response(['error' => 'A valid attendance record id is required.'], 422);
}
try {
	$statement = database()->prepare('DELETE FROM attendance WHERE id = :id');
	$statement->execute(['id' => $attendanceId]);
	if ($statement->rowCount() === 0) {
		api_response(['error' => 'Attendance record not found.'], 404);
	}
	api_response(['message' => 'Attendance record removed.']);
} catch (PDOException $exception) {
	api_database_error($exception);
}
