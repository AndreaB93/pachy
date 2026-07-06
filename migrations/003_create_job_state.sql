-- migrations/003_create_job_state.sql
CREATE TABLE IF NOT EXISTS job_state (
    job_key    VARCHAR(255) PRIMARY KEY,
    value      BIGINT       NOT NULL DEFAULT 0,
    updated_at DATETIME     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
