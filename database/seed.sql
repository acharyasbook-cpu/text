USE acharya_books;

-- Demo student (password: student123) — hash updated by setup.php
INSERT INTO users (name, email, phone, password_hash, role) VALUES
('రామ కృష్ణ', 'student@acharyabooks.com', '9876543210', '$2y$10$placeholder', 'student');

SET @uid = LAST_INSERT_ID();

INSERT INTO courses (slug, name, name_te, region, description, sort_order) VALUES
('ap-dsc', 'AP DSC', 'ఏపీ డీఎస్సీ', 'Andhra Pradesh', 'District Selection Committee — SGT, SA, TGT, PET & Language Pandit preparation.', 1),
('ts-dsc', 'TS DSC', 'తెలంగాణ డీఎస్సీ', 'Telangana', 'Telangana DSC Paper I & II with state-specific modules.', 2),
('ap-tet', 'AP TET', 'ఏపీ టెట్', 'Andhra Pradesh', 'Andhra Pradesh Teacher Eligibility Test — Paper I & II.', 3),
('ts-tet', 'TS TET', 'తెలంగాణ టెట్', 'Telangana', 'Telangana TET bilingual material and mock tests.', 4),
('ctet', 'CTET', 'సీటెట్', 'National', 'Central TET Paper I (I–V) and Paper II (VI–VIII).', 5);

-- AP DSC subjects
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'telugu', 'Telugu', 'తెలుగు', 1 FROM courses WHERE slug='ap-dsc';
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'english', 'English', 'ఇంగ్లీష్', 2 FROM courses WHERE slug='ap-dsc';
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'maths', 'Mathematics', 'గణితం', 3 FROM courses WHERE slug='ap-dsc';
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'psychology', 'Psychology', 'మనస్తత్వశాస్త్రం', 4 FROM courses WHERE slug='ap-dsc';
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'general-knowledge', 'General Knowledge', 'సాధారణ జ్ఞానం', 5 FROM courses WHERE slug='ap-dsc';

-- TS DSC subjects
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'telugu', 'Telugu', 'తెలుగు', 1 FROM courses WHERE slug='ts-dsc';
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'maths', 'Mathematics', 'గణితం', 2 FROM courses WHERE slug='ts-dsc';
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'child-development', 'Child Development', 'బాల వికాసం', 3 FROM courses WHERE slug='ts-dsc';

-- AP TET
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'pedagogy', 'Pedagogy', 'బోధనా పద్ధతులు', 1 FROM courses WHERE slug='ap-tet';
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'telugu', 'Telugu', 'తెలుగు', 2 FROM courses WHERE slug='ap-tet';

-- TS TET
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'telugu', 'Telugu', 'తెలుగు', 1 FROM courses WHERE slug='ts-tet';
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'maths', 'Mathematics', 'గణితం', 2 FROM courses WHERE slug='ts-tet';

-- CTET
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'cdp', 'Child Development & Pedagogy', 'బాల వికాసం & బోధన', 1 FROM courses WHERE slug='ctet';
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'mathematics', 'Mathematics', 'గణితం', 2 FROM courses WHERE slug='ctet';
INSERT INTO subjects (course_id, slug, name, name_te, sort_order)
SELECT id, 'environmental', 'Environmental Studies', 'పర్యావరణ అధ్యయనం', 3 FROM courses WHERE slug='ctet';

-- Sample lessons (AP DSC Telugu)
INSERT INTO lessons (subject_id, slug, title, title_te, summary, duration_mins, sort_order, is_free_preview)
SELECT s.id, 'alphabet-grammar', 'Alphabet & Grammar Basics', 'అక్షరమాల & వ్యాకరణ ప్రాథమికాలు',
       'Foundation topics for DSC Telugu paper.', 45, 1, 1
FROM subjects s JOIN courses c ON s.course_id=c.id WHERE c.slug='ap-dsc' AND s.slug='telugu';

INSERT INTO lessons (subject_id, slug, title, title_te, summary, duration_mins, sort_order)
SELECT s.id, 'poetry-analysis', 'Poetry Analysis', 'కవితా విశ్లేషణ',
       'Major poets and literary devices.', 60, 2
FROM subjects s JOIN courses c ON s.course_id=c.id WHERE c.slug='ap-dsc' AND s.slug='telugu';

-- Study materials
INSERT INTO study_materials (subject_id, title, material_type, description, sort_order)
SELECT s.id, 'Telugu Grammar PDF', 'pdf', 'Complete grammar reference for AP DSC.', 1
FROM subjects s JOIN courses c ON s.course_id=c.id WHERE c.slug='ap-dsc' AND s.slug='telugu';

-- Sub-course packages
INSERT INTO sub_course_packages (slug, package_type, course_id, subject_id, name, name_te, description, price_inr, includes_division_tests)
SELECT 'ap-dsc-telugu', 'subject', c.id, s.id, 'AP DSC — Telugu Subject', 'ఏపీ డీఎస్సీ — తెలుగు', 'Full Telugu subject access with lessons.', 999.00, 0
FROM courses c JOIN subjects s ON s.course_id=c.id WHERE c.slug='ap-dsc' AND s.slug='telugu';

INSERT INTO sub_course_packages (slug, package_type, course_id, subject_id, name, name_te, description, price_inr, includes_division_tests)
SELECT 'ap-dsc-psychology', 'subject', c.id, s.id, 'AP DSC — Psychology', 'ఏపీ డీఎస్సీ — మనస్తత్వశాస్త్రం', 'Psychology lessons and topic tests.', 799.00, 0
FROM courses c JOIN subjects s ON s.course_id=c.id WHERE c.slug='ap-dsc' AND s.slug='psychology';

INSERT INTO sub_course_packages (slug, package_type, course_id, subject_id, name, name_te, description, price_inr, includes_division_tests)
SELECT 'ap-dsc-division-tests', 'division_tests', c.id, NULL, 'AP DSC Division Test Pack', 'ఏపీ డీఎస్సీ డివిజన్ టెస్ట్ ప్యాక్',
       'All unit-wise Division Tests across AP DSC subjects.', 1499.00, 1
FROM courses c WHERE c.slug='ap-dsc';

-- Demo subscription: Telugu subject for demo user
INSERT INTO user_subscriptions (user_id, package_id, status)
SELECT @uid, id, 'active' FROM sub_course_packages WHERE slug='ap-dsc-telugu';

-- Tests
INSERT INTO tests (course_id, subject_id, slug, title, title_te, test_type, division_label, duration_mins, total_questions, total_marks, package_id)
SELECT c.id, s.id, 'telugu-topic-1', 'Telugu Topic Test — Grammar', 'తెలుగు టాపిక్ టెస్ట్ — వ్యాకరణ', 'topic', NULL, 30, 5, 5, p.id
FROM courses c
JOIN subjects s ON s.course_id=c.id AND s.slug='telugu'
JOIN sub_course_packages p ON p.slug='ap-dsc-telugu'
WHERE c.slug='ap-dsc';

INSERT INTO tests (course_id, subject_id, slug, title, title_te, test_type, division_label, duration_mins, total_questions, total_marks, package_id)
SELECT c.id, NULL, 'ap-dsc-division-unit-1', 'Division Test — Unit I (General)', 'డివిజన్ టెస్ట్ — యూనిట్ I', 'division', 'Unit I', 45, 5, 5, p.id
FROM courses c
JOIN sub_course_packages p ON p.slug='ap-dsc-division-tests'
WHERE c.slug='ap-dsc';

INSERT INTO tests (course_id, slug, title, title_te, test_type, duration_mins, total_questions, total_marks)
SELECT id, 'ap-dsc-grand-mock-1', 'AP DSC Grand Mock Test 1', 'ఏపీ డీఎస్సీ గ్రాండ్ మాక్ 1', 'grand', 180, 5, 5
FROM courses WHERE slug='ap-dsc';

-- Sample questions for topic test
INSERT INTO test_questions (test_id, question_order, question_text, question_text_te, option_a, option_b, option_c, option_d, correct_option, topic_tag)
SELECT t.id, 1, 'Which is the correct plural form of "పుస్తకం"?', '“పుస్తకం” యొక్క సరైన బహువచన రూపం ఏది?',
       'పుస్తకాలు', 'పుస్తకములు', 'పుస్తకానికి', 'పుస్తకంlu', 'A', 'Grammar'
FROM tests t JOIN courses c ON t.course_id=c.id WHERE t.slug='telugu-topic-1' AND c.slug='ap-dsc';

INSERT INTO test_questions (test_id, question_order, question_text, option_a, option_b, option_c, option_d, correct_option, topic_tag)
SELECT t.id, 2, 'Sandhi that combines two words is called?', 'సంధి', 'సమాసం', 'అలంకారం', 'ఛందస్సు', 'A', 'Grammar'
FROM tests t WHERE t.slug='telugu-topic-1';

INSERT INTO test_questions (test_id, question_order, question_text, option_a, option_b, option_c, option_d, correct_option)
SELECT t.id, n.n, CONCAT('Sample question ', n.n, ' for practice?'), 'Option A', 'Option B', 'Option C', 'Option D', 'B'
FROM tests t
CROSS JOIN (SELECT 3 AS n UNION SELECT 4 UNION SELECT 5) n
WHERE t.slug='telugu-topic-1';

-- Sample attempt for analytics
INSERT INTO test_attempts (user_id, test_id, submitted_at, time_taken_secs, score, max_score, correct_count, wrong_count, unanswered_count, status)
SELECT @uid, t.id, NOW() - INTERVAL 2 DAY, 1240, 38, 50, 38, 10, 2, 'submitted'
FROM tests t WHERE t.slug='ap-dsc-grand-mock-1';

INSERT INTO test_attempts (user_id, test_id, submitted_at, time_taken_secs, score, max_score, correct_count, wrong_count, unanswered_count, status)
SELECT @uid, t.id, NOW() - INTERVAL 1 DAY, 820, 4, 5, 4, 1, 0, 'submitted'
FROM tests t WHERE t.slug='telugu-topic-1';
