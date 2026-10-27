CREATE TABLE IF NOT EXISTS global_polls (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    explanation TEXT NULL,
    choice_type ENUM('single','multiple') NOT NULL DEFAULT 'single',
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY ix_global_polls_status_updated (status, updated_at),
    CONSTRAINT fk_global_polls_creator FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS global_poll_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    poll_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(255) NOT NULL,
    position SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_global_poll_options_position (poll_id, position),
    CONSTRAINT fk_global_poll_options_poll FOREIGN KEY (poll_id) REFERENCES global_polls (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS global_poll_responses (
    poll_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    option_id BIGINT UNSIGNED NOT NULL,
    answered_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (poll_id, user_id, option_id),
    CONSTRAINT fk_global_poll_responses_poll FOREIGN KEY (poll_id) REFERENCES global_polls (id) ON DELETE CASCADE,
    CONSTRAINT fk_global_poll_responses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_global_poll_responses_option FOREIGN KEY (option_id) REFERENCES global_poll_options (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
