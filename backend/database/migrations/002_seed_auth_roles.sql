-- Safe additive role seed. Does not touch users or memberships.
INSERT IGNORE INTO roles (role_key, description) VALUES
    ('portal_admin', 'Global portal administration'),
    ('campaign_dm', 'Campaign-level dungeon master'),
    ('campaign_member', 'Campaign member');
