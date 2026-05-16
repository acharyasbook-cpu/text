-- Example: attach six PET “Subject Titlers” to AP DSC PET programme workspace.
-- Adjust @course_id / @pet_sub_course_id to match your database (see queries below).
-- Safe approach: run `php database/migrate_dynamic_hierarchy.php` first, then extend PET pivots.
--
-- Resolve IDs:
--   SELECT id FROM courses WHERE slug = 'ap-dsc' LIMIT 1;
--   SELECT id FROM sub_courses WHERE course_id = ? AND slug = 'pet' LIMIT 1;

SET @course_id := (SELECT id FROM courses WHERE slug = 'ap-dsc' LIMIT 1);
SET @pet_sc_id := (SELECT id FROM sub_courses WHERE course_id = @course_id AND slug = 'pet' LIMIT 1);

-- Subjects: English UI name + Telugu display (Noto Sans Telugu on front-end).
-- Slugs are namespaced per flagship course to avoid collisions.
INSERT INTO subjects (course_id, category_id, slug, name, name_te, description, sort_order, status, is_active)
VALUES
  (@course_id, NULL, 'ap-dsc-pet-gk-current-affairs', 'GK & Current Affairs', 'జీకే & కరెంట్ అఫైర్స్', NULL, 10, 1, 1),
  (@course_id, NULL, 'ap-dsc-pet-perspective-education', 'Perspective in Education', 'పర్ స్పెక్టివ్ ఇన్ ఎడ్యుకేషన్', NULL, 20, 1, 1),
  (@course_id, NULL, 'ap-dsc-pet-classroom-psychology', 'Classroom Psychology', 'క్లాస్ రూమ్ సైకాలజీ', NULL, 30, 1, 1),
  (@course_id, NULL, 'ap-dsc-pet-content', 'Content', 'కంటెంట్', NULL, 40, 1, 1),
  (@course_id, NULL, 'ap-dsc-pet-methodology', 'Methodology', 'మెథడాలజీ', NULL, 50, 1, 1),
  (@course_id, NULL, 'ap-dsc-pet-english-proficiency', 'English Proficiency Test', 'ఇంగ్లీష్ ప్రొఫిషియన్సీ టెస్ట్', NULL, 60, 1, 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  name_te = VALUES(name_te),
  sort_order = VALUES(sort_order),
  status = 1,
  is_active = 1;

-- Link each subject row to the PET sub-course (pivot).
INSERT IGNORE INTO sub_course_subjects (sub_course_id, subject_id, sort_order, status, is_active)
SELECT @pet_sc_id, s.id, s.sort_order, 1, 1
FROM subjects s
WHERE s.slug IN (
  'ap-dsc-pet-gk-current-affairs',
  'ap-dsc-pet-perspective-education',
  'ap-dsc-pet-classroom-psychology',
  'ap-dsc-pet-content',
  'ap-dsc-pet-methodology',
  'ap-dsc-pet-english-proficiency'
)
AND @pet_sc_id IS NOT NULL;

-- Optional: seed five tier placeholders per subject (tests.test_type = topic|division|revision|grand|model).
-- Uncomment if your `tests` table matches this shape (see live schema / migrate scripts).

/*
INSERT INTO tests (course_id, subject_id, slug, title, test_type, sort_order, status, is_active)
SELECT @course_id, s.id, CONCAT(s.slug, '-topic-1'), 'Topic test 1', 'topic', 0, 1, 1 FROM subjects s WHERE s.slug LIKE 'ap-dsc-pet-%';
*/
