# Wijzigingen

| Onderdeel | Status | Bewijs |
|---|---|---|
| Canonieke migratiebron | Geïmplementeerd | `backend/database/migrations/001` t/m `004`; oude map verwijderd |
| .env laden | Geïmplementeerd | `Config::loadDotEnv()` in frontcontroller en CLI-scripts |
| Testdatabase | Geïmplementeerd | `backend/database/compose.test.yml`, MariaDB 11.4, 127.0.0.1:3307 |
| Migratierunner | Geïmplementeerd | `backend/scripts/migrate.php`, checksum- en testdatabaseveiligheid |
| Eerste admin | Geïmplementeerd | CLI-only, interactieve wachtwoordinvoer |
| Backend testharnas | Functioneel getest | SQL 4/4, HTTP-smoke en authmatrix 7/7; matrix draait op unieke per-run database |
| Invite registratie | Functioneel getest | echte browserflow genereert, trekt in, vervangt en activeert invite |
| Userlijst/detail/bewerken | Functioneel getest | echte browserflow en HTTP resource/autorisatiechecks |
| Login/logout/me/reset | Functioneel getest | echte browserflow plus sessie-/codechecks |
| Browserintegratie echte backend | Functioneel getest | definitieve runner: 3/3 |
| Betrouwbare testuitvoering | Functioneel getest | Runner met unieke PID-database, exitcodecontrole, eigen PHP-proces en poortcontrole |
| Invite/reset adminacties | Aangesloten, statisch gecontroleerd | Adminpagina’s, gedeelde `CopyButton`, server-side issue/revoke |
| Volledige browserflow | Functioneel getest | `auth-real.spec.ts`, 3/3 scenario’s geslaagd |
| Codeconcurrentie en rate limit | Functioneel getest | twee PHP-processen/PDO-verbindingen; 1 succes, rate limit 429 |
| Invite replacement bug | Geïmplementeerd en functioneel getest | invited profiel wordt hergebruikt; code blijft case-sensitive |
| Laatste-admininvariant | Geïmplementeerd en functioneel getest | actieve adminset wordt transactioneel gelockt; self-demote/self-disable en twee-process concurrencytest |
| Disable-route-ID | Geïmplementeerd en functioneel getest | `/users/{id}/disable` gebruikt expliciet het ID-segment |
| Laatste-adminmatrix | Functioneel getest | demote/demote, disable/disable en demote/disable met overlappende processen; database eindigt steeds met minimaal één actieve admin |
