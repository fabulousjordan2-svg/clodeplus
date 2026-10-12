USE codeplus;

START TRANSACTION;

UPDATE grades
SET course_id = NULL, subject_id = NULL
WHERE course_id IN (
	SELECT c.id
	FROM courses AS c
	JOIN fields_of_study AS f ON f.id = c.field_of_study_id
	JOIN academies AS a ON a.id = f.academy_id
	WHERE a.name IN ('General Academy', 'Codeplus College')
)
OR subject_id IN (
	SELECT s.id
	FROM subjects AS s
	JOIN courses AS c ON c.id = s.course_id
	JOIN fields_of_study AS f ON f.id = c.field_of_study_id
	JOIN academies AS a ON a.id = f.academy_id
	WHERE a.name IN ('General Academy', 'Codeplus College')
);

DELETE s
FROM subjects AS s
JOIN courses AS c ON c.id = s.course_id
JOIN fields_of_study AS f ON f.id = c.field_of_study_id
JOIN academies AS a ON a.id = f.academy_id
WHERE a.name IN ('General Academy', 'Codeplus College');

DELETE c
FROM courses AS c
JOIN fields_of_study AS f ON f.id = c.field_of_study_id
JOIN academies AS a ON a.id = f.academy_id
WHERE a.name IN ('General Academy', 'Codeplus College');

DELETE f
FROM fields_of_study AS f
JOIN academies AS a ON a.id = f.academy_id
WHERE a.name IN ('General Academy', 'Codeplus College');

DELETE FROM academies
WHERE name IN ('General Academy', 'Codeplus College');

COMMIT;
