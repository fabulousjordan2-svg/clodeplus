USE codeplus;

CREATE TABLE IF NOT EXISTS fields_of_study (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(120) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY fields_of_study_name_unique (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	field_of_study_id BIGINT UNSIGNED NOT NULL,
	name VARCHAR(120) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY courses_field_name_unique (field_of_study_id, name),
	CONSTRAINT courses_field_fk FOREIGN KEY (field_of_study_id) REFERENCES fields_of_study (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subjects (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	course_id BIGINT UNSIGNED NOT NULL,
	name VARCHAR(120) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY subjects_course_name_unique (course_id, name),
	CONSTRAINT subjects_course_fk FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

ALTER TABLE students
	ADD COLUMN field_of_study_id BIGINT UNSIGNED NULL AFTER user_id,
	ADD CONSTRAINT students_field_fk FOREIGN KEY (field_of_study_id) REFERENCES fields_of_study (id) ON DELETE SET NULL;

ALTER TABLE grades
	ADD COLUMN course_id BIGINT UNSIGNED NULL AFTER student_id,
	ADD COLUMN subject_id BIGINT UNSIGNED NULL AFTER course_id,
	ADD CONSTRAINT grades_course_fk FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE RESTRICT,
	ADD CONSTRAINT grades_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE RESTRICT;

CREATE OR REPLACE VIEW student_grade_warehouse AS
	SELECT
		s.id AS student_id,
		s.student_number,
		s.full_name,
		s.email,
		s.location,
		s.grade_level,
		f.name AS field_of_study,
		COUNT(g.id) AS grade_count,
		ROUND(AVG(g.score / NULLIF(g.max_score, 0) * 100), 2) AS average_percentage,
		COALESCE(SUM(g.score), 0) AS total_score,
		COALESCE(SUM(g.max_score), 0) AS total_possible,
		MAX(g.updated_at) AS latest_grade_update,
		COALESCE(a.attendance_count, 0) AS attendance_count,
		COALESCE(a.present_count, 0) AS present_count,
		COALESCE(a.absent_count, 0) AS absent_count,
		COALESCE(a.late_count, 0) AS late_count,
		COALESCE(a.excused_count, 0) AS excused_count,
		a.attendance_percentage
	FROM students AS s
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
	) AS a ON a.student_id = s.id
	GROUP BY s.id, s.student_number, s.full_name, s.email, s.location, s.grade_level, f.name,
		a.attendance_count, a.present_count, a.absent_count, a.late_count, a.excused_count,
		a.attendance_percentage;
