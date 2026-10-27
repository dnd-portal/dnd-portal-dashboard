-- Additive fields required for poll semantics and traceable payment corrections.
ALTER TABLE polls ADD COLUMN choice_type ENUM('single','multiple') NOT NULL DEFAULT 'single' AFTER status;
ALTER TABLE payment_requests ADD COLUMN external_url VARCHAR(2048) NULL AFTER currency;
ALTER TABLE payment_entries ADD COLUMN correction_of_entry_id BIGINT UNSIGNED NULL AFTER entered_by;
ALTER TABLE payment_entries ADD CONSTRAINT fk_payment_entries_correction FOREIGN KEY (correction_of_entry_id) REFERENCES payment_entries (id) ON DELETE RESTRICT ON UPDATE CASCADE;
