ALTER TABLE registration_invites
    ADD COLUMN consumed_by_user_id BIGINT UNSIGNED NULL AFTER consumed_at,
    ADD COLUMN revoked_at DATETIME(6) NULL AFTER consumed_by_user_id,
    ADD CONSTRAINT fk_registration_invites_consumed_by
        FOREIGN KEY (consumed_by_user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD KEY ix_registration_invites_state (consumed_at, revoked_at, expires_at);
