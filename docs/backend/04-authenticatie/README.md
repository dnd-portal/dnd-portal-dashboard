# Authenticatie

## Status

- **Geslaagd:** echte PHP-authenticatiebasis toegevoegd volgens het datamodel: login, logout, me, database-backed herroepbare sessies, password hashing/rehash, CSRF/origincontrole, rate limiting, invite-activatie, adminuitgifte van invites/resetcodes en atomische reset.
- **Geslaagd:** codes zijn 128-bit random materiaal en alleen hashes worden opgeslagen.
- **Niet uitgevoerd:** MySQL-integratietests en InfinityFree-tests; de lokale omgeving heeft geen MySQL/MariaDB.
- **Bewust niet toegevoegd:** e-maildienst, automatische login na reset, Google/Discord en domeinfeatures.

De bestaande mockdata blijft leidend voor het dashboard totdat afzonderlijke domeinfeatures volledig naar de API zijn gemigreerd.

