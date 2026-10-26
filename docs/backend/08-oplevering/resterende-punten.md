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
- [ ] Campagne-DM en speler als afzonderlijke browsercontext voor alle sessiemutaties; de backendmatrix test deelnemer/buitenstaander, maar niet de volledige browserrechtenmatrix.
- [ ] Tijdzoneconversie, zomer-/wintertijd en ambigue/niet-bestaande lokale tijden. Het huidige contract bewaart `starts_at` als TIME en heeft geen expliciete lokale-timezonevalidatie.
- [x] Beschikbaarheid via echte browsercontext opgeslagen, herladen via API gecontroleerd en verwijderd; jaarwisseling `2026-12-31` → `2027-01-01` en datumbehoud getest.
- [ ] Schrikkeldag in de mutatieflow; `2028-02-29` is wel als grensrequest gecontroleerd, maar door het bestaande ±366-dagenbeleid niet als normale opslagflow bewezen.
- [ ] Dashboardbrowser uitbreiden met expliciet gecontroleerde sessie/RSVP-totalen, open polls, lege toestand, API-uitval en afzonderlijke valuta.
- [ ] Bestaande-data-migratie uitvoeren met synthetische representatieve brondata.
- [ ] Databasebackup maken en herstellen naar een afzonderlijke testdatabase en daarna kernflows vergelijken.
- [ ] Resterende mockgebonden productieroutes binnen de bestaande scope verwijderen of ondubbelzinnig als demo markeren.

## Productbesluiten met aanbeveling

- [ ] RSVP-deadlines en wijzigingen na de deadline — aanbeveling: behoud de bestaande `rsvp_deadline_at`-controle en leg alleen vast of beheerders na de deadline mogen corrigeren.
- [ ] `responded_without_option` — aanbeveling: houd de huidige semantiek (geen stemrij = niet gereageerd) totdat product expliciet een derde toestand nodig heeft.
- [ ] Financieel beleid voor negatieve correcties/verrekeningen — aanbeveling: gebruik uitsluitend append-only reversals binnen dezelfde verplichting/valuta; voeg verrekening pas toe na expliciete keuze.
- [ ] Dashboardactiviteit — aanbeveling: houd actorinformatie voorlopig weg; eventtype/tijd voldoen aan de bestaande privacy-inrichting.

## Uitsluitend InfinityFree-validatie

- [ ] PHP-versie en benodigde extensies.
- [ ] HTTPS, Secure/HttpOnly/SameSite-cookies en Origin/CSRF op de echte hostname.
- [ ] `/api/v1/`-routing en statische frontenddeployment.
- [ ] Blokkade van interne PHP-code, `.env`, migraties, tests, backups en logs vanaf de webroot.
- [ ] InnoDB-constraints, transacties, locking en migratievolgorde op hosting.
- [ ] Eerste-adminprocedure zonder SSH en uitschakelen van tijdelijke diagnosevoorzieningen.
- [ ] Backup/herstel en productie-smokeflow zonder livegegevens te wijzigen.

Zolang lokale open punten of InfinityFree-controles niet zijn afgevinkt, is de applicatie niet productiegeschikt verklaard.
