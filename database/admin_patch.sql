USE acharya_books;

ALTER TABLE tests
  ADD COLUMN IF NOT EXISTS passing_marks SMALLINT UNSIGNED NOT NULL DEFAULT 25 AFTER total_marks;

ALTER TABLE test_questions
  ADD COLUMN IF NOT EXISTS explanation TEXT NULL AFTER correct_option;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED DEFAULT NULL,
    amount_inr DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(40) DEFAULT 'manual',
    transaction_ref VARCHAR(120) DEFAULT NULL,
    status ENUM('completed','pending','failed','refunded') NOT NULL DEFAULT 'completed',
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES sub_course_packages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Demo admin (password set by admin_setup.php)
INSERT IGNORE INTO users (name, email, phone, password_hash, role)
VALUES ('Platform Admin', 'admin@acharyabooks.com', NULL, '$2y$10$placeholder', 'admin');
