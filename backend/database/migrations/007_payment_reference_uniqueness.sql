-- A payment reference is an idempotency key within one obligation.
ALTER TABLE payment_entries
    ADD UNIQUE KEY uq_payment_entries_obligation_reference (obligation_id, external_reference);
