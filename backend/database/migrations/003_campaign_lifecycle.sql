-- Additive lifecycle fields; no data is deleted or reseeded.
ALTER TABLE campaigns ADD COLUMN archived_at DATETIME(6) NULL AFTER timezone;
ALTER TABLE game_sessions ADD COLUMN rsvp_deadline_at DATETIME(6) NULL AFTER ends_at;
