USE codeplus;

ALTER TABLE students
	ADD COLUMN phone VARCHAR(40) NOT NULL DEFAULT '' AFTER email,
	ADD COLUMN date_of_birth DATE NULL AFTER phone,
	ADD COLUMN gender VARCHAR(40) NOT NULL DEFAULT '' AFTER date_of_birth;

ALTER TABLE courses
	ADD COLUMN teacher_name VARCHAR(120) NOT NULL DEFAULT '' AFTER name;

ALTER TABLE attendance
	ADD COLUMN term VARCHAR(80) NOT NULL DEFAULT '' AFTER attendance_date;

CREATE OR REPLACE VIEW student_grade_warehouse AS
	SELECT
		s.id AS student_id,
		s.student_number,
		s.full_name,
		s.email,
		s.phone,
		s.date_of_birth,
		s.gender,
		s.location,
		s.grade_level,
		academy.name AS academy,
		f.name AS field_of_study,
		COUNT(g.id) AS grade_count,
		ROUND(AVG(g.score / NULLIF(g.max_score, 0) * 100), 2) AS average_percentage,
		COALESCE(SUM(g.score), 0) AS total_score,
		COALESCE(SUM(g.max_score), 0) AS total_possible,
		MAX(g.updated_at) AS latest_grade_update,
		COALESCE(attendance_summary.attendance_count, 0) AS attendance_count,
		COALESCE(attendance_summary.present_count, 0) AS present_count,
		COALESCE(attendance_summary.absent_count, 0) AS absent_count,
		COALESCE(attendance_summary.late_count, 0) AS late_count,
		COALESCE(attendance_summary.excused_count, 0) AS excused_count,
		attendance_summary.attendance_percentage
	FROM students AS s
	LEFT JOIN academies AS academy ON academy.id = s.academy_id
	LEFT JOIN fields_of_study AS f ON f.id = s.field_of_study_id
	LEFT JOIN grades AS g ON g.student_id = s.id
	LEFT JOIN (
		SELECT
			student_id,
			COUNT(*) AS attendance_count,
			SUM(status = 'present') AS present_count,
			SUM(status = 'absent') AS absent_count,
			SUM(status = 'late') AS late_count,
			SUM(status = 'excused') AS excused_count,
			ROUND(100 * SUM(status = 'present') / NULLIF(COUNT(*), 0), 2) AS attendance_percentage
		FROM attendance
		GROUP BY student_id
	) AS attendance_summary ON attendance_summary.student_id = s.id
	GROUP BY s.id, s.student_number, s.full_name, s.email, s.phone, s.date_of_birth, s.gender,
		s.location, s.grade_level, academy.name, f.name, attendance_summary.attendance_count,
		attendance_summary.present_count, attendance_summary.absent_count, attendance_summary.late_count,
		attendance_summary.excused_count, attendance_summary.attendance_percentage;
