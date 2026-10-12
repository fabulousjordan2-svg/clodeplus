USE codeplus;

CREATE TABLE IF NOT EXISTS academies (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(120) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY academies_name_unique (name)
) ENGINE=InnoDB;

ALTER TABLE fields_of_study ADD COLUMN academy_id BIGINT UNSIGNED NULL AFTER id;

INSERT INTO academies (name)
SELECT 'General Academy'
WHERE EXISTS (SELECT 1 FROM fields_of_study WHERE academy_id IS NULL)
ON DUPLICATE KEY UPDATE name = VALUES(name);

UPDATE fields_of_study
SET academy_id = (SELECT id FROM academies WHERE name = 'General Academy')
WHERE academy_id IS NULL;

ALTER TABLE fields_of_study
	DROP INDEX fields_of_study_name_unique,
	MODIFY COLUMN academy_id BIGINT UNSIGNED NOT NULL,
	ADD UNIQUE KEY fields_academy_name_unique (academy_id, name),
	ADD CONSTRAINT fields_academy_fk FOREIGN KEY (academy_id) REFERENCES academies (id) ON DELETE RESTRICT;

ALTER TABLE students
	ADD COLUMN academy_id BIGINT UNSIGNED NULL AFTER user_id,
	ADD CONSTRAINT students_academy_fk FOREIGN KEY (academy_id) REFERENCES academies (id) ON DELETE SET NULL;

UPDATE students AS s
JOIN fields_of_study AS f ON f.id = s.field_of_study_id
SET s.academy_id = f.academy_id
WHERE s.academy_id IS NULL;

CREATE OR REPLACE VIEW student_grade_warehouse AS
	SELECT
		s.id AS student_id,
		s.student_number,
		s.full_name,
		s.email,
		s.location,
		s.grade_level,
		a.name AS academy,
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
	LEFT JOIN academies AS a ON a.id = s.academy_id
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
	GROUP BY s.id, s.student_number, s.full_name, s.email, s.location, s.grade_level, a.name, f.name,
		attendance_summary.attendance_count, attendance_summary.present_count, attendance_summary.absent_count,
		attendance_summary.late_count, attendance_summary.excused_count, attendance_summary.attendance_percentage;
