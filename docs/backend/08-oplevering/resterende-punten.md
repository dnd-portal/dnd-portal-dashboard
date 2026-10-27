# Definitieve opleverchecklist

Deze lijst is de actuele bron voor de resterende lokale afronding binnen de afgesproken backend-scope. Een vinkje verwijst naar werkelijk uitgevoerd lokaal testbewijs.

## Lokaal functioneel afgerond

- [x] Migraties 001–007 vanaf nul uitgevoerd op nieuwe MariaDB-testdatabases; tweede run gaf uitsluitend `SKIP`.
- [x] Auth, sessies, CSRF/origin, rate limiting, invites, resets en laatste-adminbescherming getest.
- [x] Werkelijk overlappende adminconcurrency getest met afzonderlijke processen en databaseverbindingen.
- [x] Campagne aanmaken, archiveren, herstellen, opnieuw ophalen en versieconflict getest.
- [x] Membership POST → directe databasecontrole → GET; dubbele actieve memberships, rollen, vertrek en heractivatie getest.
- [x] Sessies: aanmaken, detail, deelnemers, RSVP, attendance, annuleren, historie en route-ID-parsing getest in backendregressies.
- [x] Polls: single/multiple choice, wijzigen, verkeerde optie-ID, niet-deelnemer, verlopen/gesloten polls, labelwijziging en browserflows getest.
- [x] Betalingen: verzoek, verplichting, deel-/volledige betaling, reversal, saldo, privacy en onbevoegde mutatie getest.
- [x] Betalingsconcurrency met overlappende processen: duplicate reference, twee reversals en betaling/correctie; database-uitkomsten en saldo-invariant gecontroleerd.
- [x] Dashboard API: eigen saldo met reversal, admin-totalen, activiteit en spelerprivacy gecontroleerd.
- [x] Dashboardbrowser: admin- en spelercontext, reload en afwezigheid van admingegevens bij speler getest.
- [x] Pollbrowserfixture geïsoleerd; login-rate-limitvervuiling tussen browserbatches opgelost zonder productie-rate-limiter uit te schakelen.
- [x] Frontend `pnpm check`, `pnpm build`, PHP-lint en formatter/diff-controle uitgevoerd.

## Nog lokaal open

- [x] Backend-sessiematrix: admin, deelnemer na membership, buitenstaander, historische deelnemer, RSVP en attendance afzonderlijk; uitgevoerd in `session_availability_matrix.php`.
- [x] Campagne-DM en speler als afzonderlijke browsercontext: `sessions-availability-real.spec.ts`; 4/4 sessiebrowserchecks geslaagd, inclusief DM-creatie en geweigerde speler-mutatie (HTTP 403).
- [x] Europe/Amsterdam-invoer met UTC-opslag/API, weigering van niet-bestaande tijden en expliciete keuze bij ambigue tijden; gecontroleerde checks voor `2026-03-29 02:30` en `2026-10-25 02:30` geslaagd. Een volledige DST-regressiesuite is niet geclaimd.
- [x] Beschikbaarheid via echte browsercontext opgeslagen, herladen via API gecontroleerd en verwijderd; jaarwisseling `2026-12-31` → `2027-01-01` en datumbehoud getest.
- [x] Schrikkeldag in de mutatieflow: `session_availability_matrix.php` slaagde met `2028-02-29`; productiebeleid ±366 dagen bleef ongewijzigd.
- [x] Dashboardbrowser/API: exacte sessie-/RSVP-totalen, lege toestand, gecontroleerde API-uitval met foutstatus, EUR/USD-scheiding en admin-/spelerprivacy geslaagd. De betaal/dashboardbatch is structureel geïsoleerd door testdatabase-only cleanup van `rate_limit_buckets`; volledige batch `3 passed`.
- [ ] Bestaande-data-migratie uitvoeren met synthetische representatieve brondata — geblokkeerd: aantoonbaar oud schema ontbreekt; exact benodigde bronnen staan in `accountmigratie.md` en `migratiebron.md`.
- [x] Databasebackup herstellen naar afzonderlijke testdatabase: runner `dnd_portal_test_run_1233620261027092517`; `schema_migrations=7`, relaties `7`, financiële nettosom `14100`, row counts gelijk.
- [x] Resterende mockgebonden adminloader vervangen door API-backed loader met behoud van bestaande componenten en autorisatie; anonieme adminnavigatie blijft data-loos en eindigt in `Admin access required`.

## Controle-instrumentatie toegevoegd

- `backend/tests/integration/backup_restore.php` maakt uitsluitend onder een geïsoleerde `dnd_portal_test_run_*`-database een aparte herstel-database, kopieert schema/data zonder de bron te wijzigen en controleert row counts, relaties, `schema_migrations` en de financiële nettosom. Volledige runner geslaagd op `dnd_portal_test_run_3223620261027094730`.
- `prepare_payment_browser_fixture.php` wist vóór de betaal/dashboardbrowsergroep uitsluitend de testdatabase-tabel `rate_limit_buckets`. De werkelijke sleutel is `login|REMOTE_ADDR`; productierate-limiting en limieten zijn niet gewijzigd.
- `docs/backend/08-oplevering/tijdzonecontract.md` legt het huidige opslag/API/weergavecontract vast en claimt geen DST-test.
- `docs/backend/08-oplevering/migratiebron.md` legt vast dat een aantoonbaar oud schema ontbreekt; er is geen synthetische bron verzonnen.

## Productbesluiten met aanbeveling

- [x] RSVP na deadline: speler geweigerd; managercorrectie en afzonderlijke attendance toegestaan en beide auditgelogd.
- [x] `responded_without_option`: bodyloze POST kreeg eerder geen CSRF-header in de helper; muterende requests sturen nu altijd de token. Backendtest geeft HTTP 200, status `responded_without_option`, herlaadstatus en nul optiestemmen; gesloten/verlopen en niet-deelnemer blijven geweigerd.
- [x] Pollbrowserflows: oorzaak was campagnekeuze in fixture (`data[0]`) terwijl speler-membership op de fixturecampagne zat; createPoll selecteert nu via echte campaign/member-responses. Beide single-/multiple-flows geslaagd; “No option fits” opslaan/herladen en daarna optiestem opslaan getest.
- [ ] Financieel beleid voor negatieve correcties/verrekeningen — aanbeveling: gebruik uitsluitend append-only reversals binnen dezelfde verplichting/valuta; voeg verrekening pas toe na expliciete keuze.
- [x] Dashboardactiviteit: alleen portaladmin-response-allowlist bevat actornaam/actie; geen secrets of emailadressen.

## Uitsluitend InfinityFree-validatie

- [ ] PHP-versie en benodigde extensies.
- [ ] HTTPS, Secure/HttpOnly/SameSite-cookies en Origin/CSRF op de echte hostname.
- [ ] `/api/v1/`-routing en statische frontenddeployment.
- [ ] Blokkade van interne PHP-code, `.env`, migraties, tests, backups en logs vanaf de webroot.
- [ ] InnoDB-constraints, transacties, locking en migratievolgorde op hosting.
- [ ] Eerste-adminprocedure zonder SSH en uitschakelen van tijdelijke diagnosevoorzieningen.
- [ ] Backup/herstel en productie-smokeflow zonder livegegevens te wijzigen.

Zolang lokale open punten of InfinityFree-controles niet zijn afgevinkt, is de applicatie niet productiegeschikt verklaard.
