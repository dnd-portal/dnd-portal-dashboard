# Relaties

```mermaid
erDiagram
    users ||--o| user_credentials : has
    users ||--o{ user_roles : receives
    roles ||--o{ user_roles : grants
    users ||--o{ auth_sessions : owns
    users ||--o{ registration_invites : creates
    users ||--o{ password_reset_tokens : resets
    users ||--o{ campaigns : creates
    campaigns ||--o{ campaign_memberships : contains
    users ||--o{ campaign_memberships : joins
    campaigns ||--o{ game_sessions : schedules
    users ||--o{ game_sessions : creates
    game_sessions ||--o{ session_participants : invites
    users ||--o{ session_participants : attends
    users ||--o{ user_availability : declares
    campaigns ||--o{ polls : owns
    polls ||--o{ poll_options : offers
    polls ||--o{ poll_participants : targets
    users ||--o{ poll_participants : answers
    poll_participants ||--o| poll_votes : casts
    poll_options ||--o{ poll_votes : receives
    campaigns ||--o{ payment_requests : has
    payment_requests ||--o{ payment_obligations : assigns
    users ||--o{ payment_obligations : owes
    payment_obligations ||--o{ payment_entries : records
    users ||--o{ audit_events : acts
```

## Belangrijke invarianten

- Eén profiel blijft bestaan vóór en na registratie; `account_status` verandert van `invited` naar `active`.
- Login accepteert email of username, maar beide kolommen zijn afzonderlijk uniek na normalisatie. Een identifier die theoretisch met beide velden matcht, wordt geweigerd als ambigu; de applicatie mag geen willekeurige rij kiezen.
- Een campaign-DM is `campaign_memberships.membership_role='dm'`; portaladmin is uitsluitend `user_roles.role_key='portal_admin'`.
- Een afwezig `user_availability`-record betekent onbekend.
- Een poll vote vereist zowel een participant-record als een option van dezelfde poll.
- Dashboardtotalen komen uit query’s over deelnemers, obligations en entries.

