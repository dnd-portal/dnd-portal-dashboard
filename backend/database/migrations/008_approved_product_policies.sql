-- Approved product policies: explicit poll response state and audit payload support.
ALTER TABLE poll_participants
    ADD COLUMN response_state ENUM('pending','voted','responded_without_option') NOT NULL DEFAULT 'pending' AFTER invited_at;
