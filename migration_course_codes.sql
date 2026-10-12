USE codeplus;

ALTER TABLE courses
	ADD COLUMN course_code VARCHAR(40) NULL AFTER name,
	ADD UNIQUE KEY courses_code_unique (course_code);
