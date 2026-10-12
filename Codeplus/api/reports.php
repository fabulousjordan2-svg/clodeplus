<?php
declare(strict_types=1);

require __DIR__ . '/common.php';

$user = api_user();
api_require_method(['GET']);

try {
	if ($user['role'] === 'admin') {
		$statement = database()->query(
			'SELECT student_id, student_number, full_name, email, phone, date_of_birth, gender, location, grade_level, academy, field_of_study, grade_count,
			average_percentage, total_score, total_possible, latest_grade_update, attendance_count,
			attendance_percentage, present_count, absent_count, late_count, excused_count
			FROM student_grade_warehouse ORDER BY full_name, student_id'
		);
	} else {
		$statement = database()->prepare(
			'SELECT student_id, student_number, full_name, email, phone, date_of_birth, gender, location, grade_level, academy, field_of_study, grade_count,
			average_percentage, total_score, total_possible, latest_grade_update, attendance_count,
			attendance_percentage, present_count, absent_count, late_count, excused_count
			FROM student_grade_warehouse WHERE student_id = (
				SELECT id FROM students WHERE user_id = :user_id LIMIT 1
			)'
		);
		$statement->execute(['user_id' => $user['id']]);
	}
	api_response(['report' => $statement->fetchAll()]);
} catch (PDOException $exception) {
	api_database_error($exception);
}
