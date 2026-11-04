CREATE TABLE IF NOT EXISTS todos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#7560df',
    starts_at DATETIME NOT NULL,
    due_at DATETIME NOT NULL,
    main_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (completed_at, due_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
