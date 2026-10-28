-- Global availability polls are multi-select. Existing single selections remain
-- valid as one selected option after this forward migration.
UPDATE global_polls
SET choice_type = 'multiple'
WHERE choice_type = 'single';

ALTER TABLE global_polls
    MODIFY choice_type ENUM('single', 'multiple') NOT NULL DEFAULT 'multiple';
