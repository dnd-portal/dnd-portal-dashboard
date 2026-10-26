-- Multiple-choice polls store one row per selected option.
ALTER TABLE poll_votes
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (poll_id, user_id, option_id);
