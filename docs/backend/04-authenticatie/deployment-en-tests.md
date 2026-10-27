# Deployment en tests

## Lokaal

1. Maak `backend/.env` uit `.env.example`; vul databasegegevens, `APP_ENV=development`, `APP_ALLOWED_ORIGIN=http://localhost:5173` en TTL’s in.
2. Pas de niet-destructieve schema-migratie toe in een geïsoleerde database; nooit op een bestaande hostingdatabase zonder backup/dry-run.
3. Start `php -S 127.0.0.1:8099 -t public router.php` vanuit `backend/` en Vite vanuit `player/`.
4. Controleer eerst `/api/v1/health`, daarna CSRF, login, me en logout.

## InfinityFree

Controleer eerst PHP-versie, `pdo_mysql`, `password_hash`-algoritme, cookie Secure-gedrag, HTTPS, webroot en MySQL-variant. Upload geen `.env`, `src/`, migrations of tests als publiek leesbare bestanden. Zet `APP_ENV=production`, de echte HTTPS-origin en sterke databasegegevens server-side. De backend maakt geen publieke installatie- of migratieroute.

## Testmatrix

De vereiste geïsoleerde MySQL-testdatabase moet minimaal testen: juiste/verkeerde login, email/username, logout en sessierotatie; disabled user; idle/absolute expiry; CSRF en verkeerde Origin; rate-limitgrens; invite geldig/verkeerd/verlopen/ingetrokken/parallel gebruik; reset geldig/verkeerd/verlopen/parallel gebruik; password rehash; reset trekt sessies in; admin versus DM versus normale gebruiker; audit-events.

**Status:** PHPUnit/integratietests zijn niet uitgevoerd omdat geen lokale MySQL/MariaDB beschikbaar is. PHP syntaxchecks zijn wel uitgevoerd. Frontendcheck/build moeten na de UI-koppeling opnieuw worden uitgevoerd. Hostingchecks blijven apart en open.

