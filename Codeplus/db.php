<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function academic_catalog(): array
{
	$connection = database();

	return [
		'academies' => $connection->query(
			'SELECT id, name FROM academies ORDER BY name'
		)->fetchAll(),
		'fields_of_study' => $connection->query(
			'SELECT f.id, f.name, f.academy_id, a.name AS academy
			FROM fields_of_study AS f
			JOIN academies AS a ON a.id = f.academy_id
			ORDER BY a.name, f.name'
		)->fetchAll(),
		'courses' => $connection->query(
			'SELECT c.id, c.name, c.course_code,
				COALESCE(NULLIF(c.teacher_name, \'\'), (
					SELECT GROUP_CONCAT(i.name ORDER BY i.name SEPARATOR \', \')
					FROM instructor_courses AS ic
					JOIN instructors AS i ON i.id = ic.instructor_id
					WHERE ic.course_id = c.id
				)) AS teacher_name,
				c.field_of_study_id,
				f.name AS field_of_study, f.academy_id, a.name AS academy
			FROM courses AS c
			JOIN fields_of_study AS f ON f.id = c.field_of_study_id
			JOIN academies AS a ON a.id = f.academy_id
			ORDER BY a.name, f.name, c.name'
		)->fetchAll(),
		'subjects' => $connection->query(
			'SELECT s.id, s.name, s.course_id, c.name AS course_name,
				c.field_of_study_id, f.academy_id, a.name AS academy
			FROM subjects AS s
			JOIN courses AS c ON c.id = s.course_id
			JOIN fields_of_study AS f ON f.id = c.field_of_study_id
			JOIN academies AS a ON a.id = f.academy_id
			ORDER BY a.name, c.name, s.name'
		)->fetchAll(),
		'instructors' => $connection->query(
			'SELECT id, name FROM instructors ORDER BY name'
		)->fetchAll(),
		'instructor_courses' => $connection->query(
			'SELECT ic.instructor_id, ic.course_id, c.name AS course_name,
				f.name AS field_of_study, a.name AS academy
			FROM instructor_courses AS ic
			JOIN courses AS c ON c.id = ic.course_id
			JOIN fields_of_study AS f ON f.id = c.field_of_study_id
			JOIN academies AS a ON a.id = f.academy_id
			ORDER BY a.name, f.name, c.name'
		)->fetchAll(),
	];
}