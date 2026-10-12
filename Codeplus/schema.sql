CREATE DATABASE IF NOT EXISTS codeplus
	CHARACTER SET utf8mb4
	COLLATE utf8mb4_unicode_ci;

USE codeplus;

CREATE TABLE IF NOT EXISTS users (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(120) NOT NULL,
	email VARCHAR(254) NOT NULL,
	password_hash VARCHAR(255) NOT NULL,
	role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS academies (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(120) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY academies_name_unique (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fields_of_study (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	academy_id BIGINT UNSIGNED NOT NULL,
	name VARCHAR(120) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY fields_academy_name_unique (academy_id, name),
	CONSTRAINT fields_academy_fk FOREIGN KEY (academy_id) REFERENCES academies (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	field_of_study_id BIGINT UNSIGNED NOT NULL,
	name VARCHAR(120) NOT NULL,
	course_code VARCHAR(40) NULL,
	teacher_name VARCHAR(120) NOT NULL DEFAULT '',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY courses_field_name_unique (field_of_study_id, name),
	UNIQUE KEY courses_code_unique (course_code),
	CONSTRAINT courses_field_fk FOREIGN KEY (field_of_study_id) REFERENCES fields_of_study (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS instructors (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(120) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY instructors_name_unique (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS instructor_courses (
	instructor_id BIGINT UNSIGNED NOT NULL,
	course_id BIGINT UNSIGNED NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (instructor_id, course_id),
	KEY instructor_courses_course_idx (course_id),
	CONSTRAINT instructor_courses_instructor_fk FOREIGN KEY (instructor_id) REFERENCES instructors (id) ON DELETE CASCADE,
	CONSTRAINT instructor_courses_course_fk FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
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

CREATE TABLE IF NOT EXISTS students (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id BIGINT UNSIGNED NULL,
	academy_id BIGINT UNSIGNED NULL,
	field_of_study_id BIGINT UNSIGNED NULL,
	student_number VARCHAR(40) NULL,
	full_name VARCHAR(120) NOT NULL,
	email VARCHAR(254) NOT NULL,
	phone VARCHAR(40) NOT NULL DEFAULT '',
	date_of_birth DATE NULL,
	gender VARCHAR(40) NOT NULL DEFAULT '',
	location VARCHAR(160) NOT NULL DEFAULT '',
	grade_level VARCHAR(40) NOT NULL DEFAULT '',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY students_user_unique (user_id),
	UNIQUE KEY students_number_unique (student_number),
	UNIQUE KEY students_email_unique (email),
	CONSTRAINT students_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
	CONSTRAINT students_academy_fk FOREIGN KEY (academy_id) REFERENCES academies (id) ON DELETE SET NULL,
	CONSTRAINT students_field_fk FOREIGN KEY (field_of_study_id) REFERENCES fields_of_study (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grades (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	student_id BIGINT UNSIGNED NOT NULL,
	course_id BIGINT UNSIGNED NULL,
	subject_id BIGINT UNSIGNED NULL,
	subject VARCHAR(120) NOT NULL,
	term VARCHAR(80) NOT NULL,
	score DECIMAL(7,2) NOT NULL,
	max_score DECIMAL(7,2) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY grades_student_term_idx (student_id, term),
	CONSTRAINT grades_course_fk FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE RESTRICT,
	CONSTRAINT grades_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE RESTRICT,
	CONSTRAINT grades_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
	CONSTRAINT grades_score_check CHECK (score >= 0 AND max_score > 0 AND score <= max_score)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	student_id BIGINT UNSIGNED NOT NULL,
	attendance_date DATE NOT NULL,
	term VARCHAR(80) NOT NULL DEFAULT '',
	status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
	note VARCHAR(500) NOT NULL DEFAULT '',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY attendance_student_date_unique (student_id, attendance_date),
	CONSTRAINT attendance_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE
) ENGINE=InnoDB;

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
		s.location, s.grade_level, academy.name, f.name,
		attendance_summary.attendance_count, attendance_summary.present_count, attendance_summary.absent_count,
		attendance_summary.late_count, attendance_summary.excused_count, attendance_summary.attendance_percentage;