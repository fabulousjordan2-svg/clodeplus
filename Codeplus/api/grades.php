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
				'SELECT g.id, g.student_id, s.full_name AS student_name, f.name AS field_of_study,
				g.course_id, c.name AS course_name,
				COALESCE(NULLIF(c.teacher_name, \'\'), (
					SELECT GROUP_CONCAT(i.name ORDER BY i.name SEPARATOR \', \')
					FROM instructor_courses AS ic
					JOIN instructors AS i ON i.id = ic.instructor_id
					WHERE ic.course_id = c.id
				)) AS teacher_name,
				course_academy.name AS course_academy,
				g.subject_id, COALESCE(sub.name, g.subject) AS subject,
				g.term, g.score, g.max_score, g.updated_at
				FROM grades AS g JOIN students AS s ON s.id = g.student_id
				LEFT JOIN fields_of_study AS f ON f.id = s.field_of_study_id
				LEFT JOIN courses AS c ON c.id = g.course_id LEFT JOIN subjects AS sub ON sub.id = g.subject_id
				LEFT JOIN fields_of_study AS course_field ON course_field.id = c.field_of_study_id
				LEFT JOIN academies AS course_academy ON course_academy.id = course_field.academy_id
				ORDER BY s.full_name, g.term, COALESCE(sub.name, g.subject)'
			);
		} else {
			$statement = database()->prepare(
				'SELECT g.id, g.student_id, s.full_name AS student_name, f.name AS field_of_study,
				g.course_id, c.name AS course_name,
				COALESCE(NULLIF(c.teacher_name, \'\'), (
					SELECT GROUP_CONCAT(i.name ORDER BY i.name SEPARATOR \', \')
					FROM instructor_courses AS ic
					JOIN instructors AS i ON i.id = ic.instructor_id
					WHERE ic.course_id = c.id
				)) AS teacher_name,
				course_academy.name AS course_academy,
				g.subject_id, COALESCE(sub.name, g.subject) AS subject,
				g.term, g.score, g.max_score, g.updated_at
				FROM grades AS g JOIN students AS s ON s.id = g.student_id
				LEFT JOIN fields_of_study AS f ON f.id = s.field_of_study_id
				LEFT JOIN courses AS c ON c.id = g.course_id LEFT JOIN subjects AS sub ON sub.id = g.subject_id
				LEFT JOIN fields_of_study AS course_field ON course_field.id = c.field_of_study_id
				LEFT JOIN academies AS course_academy ON course_academy.id = course_field.academy_id
				WHERE s.user_id = :user_id ORDER BY g.term, COALESCE(sub.name, g.subject)'
			);
			$statement->execute(['user_id' => $user['id']]);
		}
		api_response(['grades' => $statement->fetchAll()]);
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
	$gradeId = $method === 'PUT' ? api_id($input['id'] ?? null) : null;
	$studentId = api_id($input['student_id'] ?? null);
	$subject = api_text($input['subject'] ?? null);
	$courseValue = api_text($input['course_id'] ?? null);
	$subjectValue = api_text($input['subject_id'] ?? null);
	$courseId = $courseValue === '' ? null : api_id($courseValue);
	$subjectId = $subjectValue === '' ? null : api_id($subjectValue);
	$hasCourse = $courseId !== null;
	$hasSubject = $subjectId !== null;
	$term = api_text($input['term'] ?? null);
	$score = filter_var($input['score'] ?? null, FILTER_VALIDATE_FLOAT);
	$maxScore = filter_var($input['max_score'] ?? null, FILTER_VALIDATE_FLOAT);
	if ($method === 'PUT' && $gradeId === null || $studentId === null
		|| $courseValue !== '' && $courseId === null || $subjectValue !== '' && $subjectId === null
		|| $hasCourse !== $hasSubject
		|| $term === '' || strlen($term) > 80 || $score === false || $maxScore === false
		|| $score < 0 || $maxScore <= 0 || $score > $maxScore) {
		api_response(['error' => 'Provide a valid student, subject, term, and score not greater than a positive maximum score.'], 422);
	}

	try {
		$connection = database();
		if ($courseId !== null && $subjectId !== null) {
			$catalog = $connection->prepare(
				'SELECT sub.name AS subject_name, c.field_of_study_id, f.academy_id
				FROM subjects AS sub JOIN courses AS c ON c.id = sub.course_id
				JOIN fields_of_study AS f ON f.id = c.field_of_study_id
				WHERE sub.id = :subject_id AND c.id = :course_id'
			);
			$catalog->execute(['subject_id' => $subjectId, 'course_id' => $courseId]);
			$academicOption = $catalog->fetch();
			if (!$academicOption) {
				api_response(['error' => 'Choose a subject that belongs to the selected course.'], 422);
			}
			$studentQuery = $connection->prepare('SELECT academy_id, field_of_study_id FROM students WHERE id = :id');
			$studentQuery->execute(['id' => $studentId]);
			$studentAcademic = $studentQuery->fetch();
			if (!$studentAcademic) {
				api_response(['error' => 'Student not found.'], 404);
			}
			if ($studentAcademic['academy_id'] !== null
				&& (int) $studentAcademic['academy_id'] !== (int) $academicOption['academy_id']) {
				api_response(['error' => 'The selected course belongs to a different academy than the student.'], 422);
			}
			if ($studentAcademic['field_of_study_id'] !== null
				&& (int) $studentAcademic['field_of_study_id'] !== (int) $academicOption['field_of_study_id']) {
				api_response(['error' => 'The selected course belongs to a different field of study than the student.'], 422);
			}
			$subject = $academicOption['subject_name'];
		} elseif ($subject === '' || strlen($subject) > 120) {
			api_response(['error' => 'Enter a valid subject or select a course and subject.'], 422);
		}
		if ($method === 'POST') {
			$statement = $connection->prepare(
				'INSERT INTO grades (student_id, course_id, subject_id, subject, term, score, max_score)
				VALUES (:student_id, :course_id, :subject_id, :subject, :term, :score, :max_score)'
			);
		} else {
			$statement = $connection->prepare(
				'UPDATE grades SET student_id = :student_id, course_id = :course_id, subject_id = :subject_id,
				subject = :subject, term = :term, score = :score, max_score = :max_score
				WHERE id = :id'
			);
		}
		$params = [
			'student_id' => $studentId,
			'course_id' => $courseId,
			'subject_id' => $subjectId,
			'subject' => $subject,
			'term' => $term,
			'score' => $score,
			'max_score' => $maxScore,
		];
		if ($method === 'PUT') {
			$params['id'] = $gradeId;
		}
		$statement->execute($params);
		if ($method === 'PUT' && $statement->rowCount() === 0) {
			$exists = $connection->prepare('SELECT id FROM grades WHERE id = :id');
			$exists->execute(['id' => $gradeId]);
			if (!$exists->fetch()) {
				api_response(['error' => 'Grade not found.'], 404);
			}
		}
		api_response(['message' => $method === 'POST' ? 'Grade added.' : 'Grade updated.'], $method === 'POST' ? 201 : 200);
	} catch (PDOException $exception) {
		api_database_error($exception);
	}
}

$gradeId = api_id($input['id'] ?? null);
if ($gradeId === null) {
	api_response(['error' => 'A valid grade id is required.'], 422);
}
try {
	$statement = database()->prepare('DELETE FROM grades WHERE id = :id');
	$statement->execute(['id' => $gradeId]);
	if ($statement->rowCount() === 0) {
		api_response(['error' => 'Grade not found.'], 404);
	}
	api_response(['message' => 'Grade removed.']);
} catch (PDOException $exception) {
	api_database_error($exception);
}
