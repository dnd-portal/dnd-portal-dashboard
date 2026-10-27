ALTER TABLE payment_entries
    ADD COLUMN entry_kind ENUM('payment','reversal') NOT NULL DEFAULT 'payment' AFTER status;
