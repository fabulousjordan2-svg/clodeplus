USE codeplus;

INSERT INTO academies (name)
VALUES
	('Academy of Information Technology'),
	('Academy of Management'),
	('Academy of Science'),
	('Academy of Arts'),
	('Academy of Commerce')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO fields_of_study (academy_id, name)
SELECT academy.id, seed.field_name
FROM (
	SELECT 'Information Technology' AS academy_name, 'Information Technology' AS field_name
	UNION ALL SELECT 'Information Technology', 'Computer Science'
	UNION ALL SELECT 'Business Management', 'Business Administration'
	UNION ALL SELECT 'Commerce', 'Commerce'
	UNION ALL SELECT 'Science', 'Mathematics'
	UNION ALL SELECT 'Science', 'Physics'
	UNION ALL SELECT 'Arts', 'English'
	UNION ALL SELECt  'Commerce', 'Economics'
) AS seed
JOIN academies AS academy ON academy.name = seed.academy_name
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO courses (field_of_study_id, name, course_code)
SELECT field.id, seed.course_name, seed.course_code
FROM (
	SELECT 'Academy of Information Technology' AS academy_name, 'Information Technology' AS field_name,
		'Bachelor of Science in Information Technology' AS course_name, 'BSc IT' AS course_code
	UNION ALL SELECT 'Information Technology', 'Information Technology',
		'Bachelor of Science in Computer Science' AS course_name, 'BSc CS' AS course_code
	UNION ALL SELECT 'Business Management',	
		'Bachelor of Business Administration' AS course_name, 'BBA' AS course_code
JOIN fields_of_study AS field
	ON field.academy_id = academy.id AND field.name = seed.field_name
ON DUPLICATE KEY UPDATE course_code = VALUES(course_code);
