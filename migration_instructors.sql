USE codeplus;

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
