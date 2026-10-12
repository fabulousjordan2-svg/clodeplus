<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require_once dirname(__DIR__) . '/db.php';

$user = api_user();
$method = $_SERVER['REQUEST_METHOD'];
api_require_method(['GET', 'POST', 'DELETE']);
if ($user['role'] !== 'admin') {
	api_response(['error' => 'Administrator access is required.'], 403);
}

if ($method === 'GET') {
	try {
		api_response(academic_catalog());
	} catch (PDOException $exception) {
		api_database_error($exception);
	}
}

api_require_admin_reauth();
api_require_csrf();
$input = api_input();
$type = api_text($input['type'] ?? null);
$id = api_id($input['id'] ?? null);

try {
	$connection = database();
	if ($method === 'POST') {
		$name = api_text($input['name'] ?? null);
		if ($name === '' || strlen($name) > 120) {
			api_response(['error' => 'Enter a name of up to 120 characters.'], 422);
		}

		if ($type === 'academy') {
			$statement = $connection->prepare('INSERT INTO academies (name) VALUES (:name)');
			$statement->execute(['name' => $name]);
		} elseif ($type === 'field') {
			$academyId = api_id($input['academy_id'] ?? null);
			if ($academyId === null) {
				api_response(['error' => 'Select a valid academy for this field of study.'], 422);
			}
			$exists = $connection->prepare('SELECT id FROM academies WHERE id = :id');
			$exists->execute(['id' => $academyId]);
			if (!$exists->fetch()) {
				api_response(['error' => 'The selected academy does not exist.'], 422);
			}
			$statement = $connection->prepare('INSERT INTO fields_of_study (academy_id, name) VALUES (:academy_id, :name)');
			$statement->execute(['academy_id' => $academyId, 'name' => $name]);
		} elseif ($type === 'course') {
			$courseCode = api_text($input['course_code'] ?? null);
			if (strlen($courseCode) > 40) {
				api_response(['error' => 'Enter a course code of up to 40 characters.'], 422);
			}
			$teacherName = api_text($input['teacher_name'] ?? null);
			if (strlen($teacherName) > 120) {
				api_response(['error' => 'Enter a teacher name of up to 120 characters.'], 422);
			}
			$fieldId = api_id($input['field_of_study_id'] ?? null);
			if ($fieldId === null) {
				api_response(['error' => 'Select a valid field of study for this course.'], 422);
			}
			$exists = $connection->prepare('SELECT id FROM fields_of_study WHERE id = :id');
			$exists->execute(['id' => $fieldId]);
			if (!$exists->fetch()) {
				api_response(['error' => 'The selected field of study does not exist.'], 422);
			}
			$academyId = api_id($input['academy_id'] ?? null);
			$field = $connection->prepare('SELECT academy_id FROM fields_of_study WHERE id = :id');
			$field->execute(['id' => $fieldId]);
			if ($academyId === null || (int) $field->fetchColumn() !== $academyId) {
				api_response(['error' => 'Choose a field of study that belongs to the selected academy.'], 422);
			}
			$statement = $connection->prepare('INSERT INTO courses (field_of_study_id, name, course_code, teacher_name) VALUES (:field_id, :name, :course_code, :teacher_name)');
			$statement->execute([
				'field_id' => $fieldId,
				'name' => $name,
				'course_code' => $courseCode !== '' ? $courseCode : null,
				'teacher_name' => $teacherName,
			]);
		} elseif ($type === 'subject') {
			$courseId = api_id($input['course_id'] ?? null);
			if ($courseId === null) {
				api_response(['error' => 'Select a valid course for this subject.'], 422);
			}
			$exists = $connection->prepare('SELECT id FROM courses WHERE id = :id');
			$exists->execute(['id' => $courseId]);
			if (!$exists->fetch()) {
				api_response(['error' => 'The selected course does not exist.'], 422);
			}
			$statement = $connection->prepare('INSERT INTO subjects (course_id, name) VALUES (:course_id, :name)');
			$statement->execute(['course_id' => $courseId, 'name' => $name]);
		} elseif ($type === 'instructor') {
			$courseIds = $input['course_ids'] ?? null;
			if (!is_array($courseIds) || $courseIds === []) {
				api_response(['error' => 'Select at least one course for this instructor.'], 422);
			}
			$academyId = api_id($input['academy_id'] ?? null);
			$fieldId = api_id($input['field_of_study_id'] ?? null);
			if ($academyId === null || $fieldId === null) {
				api_response(['error' => 'Select an academy and field of study for this instructor.'], 422);
			}
			$field = $connection->prepare('SELECT academy_id FROM fields_of_study WHERE id = :id');
			$field->execute(['id' => $fieldId]);
			if ((int) $field->fetchColumn() !== $academyId) {
				api_response(['error' => 'Choose a field of study that belongs to the selected academy.'], 422);
			}
			$courseIds = array_map('api_id', $courseIds);
			if (in_array(null, $courseIds, true)) {
				api_response(['error' => 'Choose valid courses for this instructor.'], 422);
			}
			$courseIds = array_values(array_unique($courseIds));
			$placeholders = implode(',', array_fill(0, count($courseIds), '?'));
			$exists = $connection->prepare(
				"SELECT COUNT(*) FROM courses WHERE field_of_study_id = ? AND id IN ({$placeholders})"
			);
			$exists->execute([$fieldId, ...$courseIds]);
			if ((int) $exists->fetchColumn() !== count($courseIds)) {
				api_response(['error' => 'Choose courses that belong to the selected academy and field of study.'], 422);
			}

			$connection->beginTransaction();
			$statement = $connection->prepare('INSERT INTO instructors (name) VALUES (:name)');
			$statement->execute(['name' => $name]);
			$instructorId = (int) $connection->lastInsertId();
			$assignment = $connection->prepare(
				'INSERT INTO instructor_courses (instructor_id, course_id) VALUES (:instructor_id, :course_id)'
			);
			foreach ($courseIds as $courseId) {
				$assignment->execute(['instructor_id' => $instructorId, 'course_id' => $courseId]);
			}
			$connection->commit();
		} else {
			api_response(['error' => 'Choose a valid academic category.'], 422);
		}
		api_response(['message' => 'Academic option added.'], 201);
	}

	if ($id === null || !in_array($type, ['academy', 'field', 'course', 'subject', 'instructor'], true)) {
		api_response(['error' => 'Choose a valid academic option to remove.'], 422);
	}
	$table = ['academy' => 'academies', 'field' => 'fields_of_study', 'course' => 'courses', 'subject' => 'subjects', 'instructor' => 'instructors'][$type];
	$statement = $connection->prepare("DELETE FROM {$table} WHERE id = :id");
	$statement->execute(['id' => $id]);
	if ($statement->rowCount() === 0) {
		api_response(['error' => 'Academic option not found.'], 404);
	}
	api_response(['message' => 'Academic option removed.']);
} catch (PDOException $exception) {
	if (isset($connection) && $connection->inTransaction()) {
		$connection->rollBack();
	}
	api_database_error($exception);
}
