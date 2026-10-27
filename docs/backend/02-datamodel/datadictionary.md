# Datadictionary

Alle IDs zijn `BIGINT UNSIGNED`. `created_at`, `updated_at` en andere instanties zijn `DATETIME(6)` in UTC. `DATE` is gereserveerd voor kalenderdagen. Geldbedragen zijn `INT UNSIGNED` in centen met een ISO-4217 `CHAR(3)`-valuta. Unieke nullable email- en username-kolommen laten meerdere `NULL`s toe, maar geen dubbele ingevulde waarden.

## Tabellen

### `users`

`id` PK; `display_name` VARCHAR(120) NOT NULL; `email` VARCHAR(254) NULL UNIQUE; `username` VARCHAR(64) NULL UNIQUE; `account_status` ENUM(`invited`,`active`,`disabled`) DEFAULT `invited`; `timezone` VARCHAR(64) DEFAULT `UTC`; `created_at`, `updated_at`; `version` INT voor optimistic locking. Indexen: status/created. Email en username worden genormaliseerd vóór opslag en vergelijking. Geen arrayvelden.

Bewaarbeleid: profielhistorie behouden; account anonimiseren volgens privacybeleid, niet verwijderen zolang audit- of financiële relaties bestaan. API: nooit wachtwoord, credentialstatus, resetgegevens of interne lockingvelden.

### `user_credentials`

`user_id` PK/FK users; `password_hash` VARCHAR(255) NOT NULL; `password_algo` VARCHAR(32); wijzigings-, lock- en failed-attemptvelden; created/updated. Eén rij per gebruiker. Alleen hash bewaren; plaintext nooit opslaan of migreren. API geeft deze tabel nooit terug.

### `roles`, `user_roles`

`roles`: `id` PK, unieke `role_key`, description. `user_roles`: samengestelde PK (`user_id`,`role_id`), created_at, beide FKs. Seed-rollen zijn minimaal `portal_admin`, `campaign_dm`, `campaign_member`; dit is autorisatie, niet loginstatus. API geeft alleen noodzakelijke effectieve rechten.

### `auth_sessions`

`id` PK; unieke `session_id_hash` BINARY(32); `user_id` FK; `session_data` MEDIUMBLOB; created/last_seen/expires/revoked; optionele hashes van IP/user-agent. Indexen op user-status en expiry. PHP gebruikt een custom PDO session handler op deze tabel: cookie bevat alleen random opaque ID; de hash in deze tabel is de enige sessiebron. Sessies verlopen/revoken, daarna periodiek verwijderen volgens bewaarbeleid. API nooit session_data of hashes.

### `registration_invites`

`id` PK; optionele `invited_user_id` FK; optionele invited_email; unieke `code_hash` BINARY(32); `created_by` FK; created/expires/consumed. Ruwe code wordt alleen direct na generatie getoond. Verlopen/verbruikte hashes blijven tijdelijk voor audit en worden daarna volgens retentie opgeschoond; niet via API uitlezen.

### `password_reset_tokens`

`id` PK; `user_id` FK; unieke token_hash BINARY(32); created/expires/used. Ruwe resetcode is alleen direct na generatie leesbaar. Gebruikte/verlopen hashes tijdelijk bewaren en daarna verwijderen. Nooit in API-responses.

### `rate_limit_buckets`

Samengestelde PK `bucket_key_hash` + `bucket_type`; window, request_count, blocked_until, updated_at. Keys zijn hashes van bijvoorbeeld IP/loginidentifier. Periodiek opruimen op updated_at; nooit ruwe identifiers in deze tabel of API.

### `campaigns`

`id` PK; name, unieke slug, `type` ENUM(`campaign`,`one_shot`), location NULL, timezone, `created_by` FK, created/updated/version. Index type/created. One-shots zijn geen aparte tabel.

### `campaign_memberships`

Samengestelde PK campaign/user; `membership_role` ENUM(`member`,`dm`); joined_at/left_at; index user/left. Een campagne-DM is dus campagnecontext; `portal_admin` blijft een aparte globale rol. Historische memberships niet cascade-verwijderen.

### `game_sessions`

`id` PK; campaign FK; title; `session_date` DATE; starts_at/ends_at UTC NULL; location; status ENUM(`planned`,`cancelled`,`completed`); creator FK; created/updated/version. Index campagne+datum en status+datum. RSVP staat niet hier.

### `session_participants`

Samengestelde PK session/user; `rsvp_status` ENUM(`pending`,`accepted`,`declined`); `attendance_status` ENUM(`unknown`,`present`,`absent`); response- en attendance-tijdstippen. RSVP en feitelijke aanwezigheid zijn afzonderlijk. Onbekende beschikbaarheid/aanwezigheid is expliciet, niet `absent`.

### `user_availability`

Samengestelde PK user/date; status ENUM(`available`,`unavailable`), note, created/updated. Geen rij betekent onbekend. Alleen expliciete invoer wordt opgeslagen.

### `polls`, `poll_options`, `poll_participants`, `poll_votes`

`polls`: campaign FK, title, status `open|closed`, closes_at, creator, created/updated/version; index campagne/status. `poll_options`: poll FK, label, position; unieke positie en label per poll. `poll_participants`: samengestelde PK poll/user. `poll_votes`: samengestelde PK poll/user, option FK. De samengestelde FK (`poll_id`,`option_id`) garandeert dat een stem bij een optie van dezelfde poll hoort; de participant-FK voorkomt stemmen buiten de doelgroep. API toont geen token- of interne metadata.

### `payment_requests`, `payment_obligations`, `payment_entries`

Request koppelt campagne/creator en bevat titel/omschrijving, optioneel totaalbedrag, valuta, status `open|closed|cancelled`, due_date, created/updated/version. Obligation koppelt request/user, bedrag/valuta, status `open|settled|waived|cancelled`, uniek per request/user. Entry koppelt obligation, bedrag/valuta, status `pending|confirmed|rejected|refunded`, `entry_kind` `payment|reversal` (migration 005), optionele externe referentie, correctieverwijzing en invoerder. Een reversal behoudt de oorspronkelijke entry en trekt hetzelfde positieve bedrag af in de serverformule. Totalen worden met `SUM(payment)-SUM(reversal)` berekend; niet dubbel opslaan. Financiële historie is RESTRICT en niet cascade delete. API nooit interne hashes; externe referenties alleen indien functioneel nodig.

### `audit_events`

`id` PK; optionele actor FK; event_type, optionele entity_type/entity_id, JSON-payload, occurred_at. Indexen op entity en actor/tijd. Append-only; actor kan bij accountverwijdering NULL worden. Payload mag geen wachtwoorden, tokens, secrets of volledige betaalgegevens bevatten.

### `schema_migrations`

`version` PK, applied_at, SHA-256 checksum. Alleen de migratierunner schrijft. Geen publieke endpoint; status alleen voor operators.

## Foreign-keybeleid

Relaties gebruiken overwegend `ON DELETE RESTRICT` om historie te beschermen. Alleen `audit_events.actor_user_id` en `payment_entries.entered_by` gebruiken `ON DELETE SET NULL`, omdat de gebeurtenis/boeking zelf behouden moet blijven. Geen cascade op gebruikers, campagnes, sessies, polls of betalingen.

