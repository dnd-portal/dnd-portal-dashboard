# Testresultaten

## Definitieve runner

Uit iedere werkdirectory uitvoeren:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File backend/scripts/run-integrations.ps1
```

De runner is gebaseerd op `$PSScriptRoot`; vanuit `player/` is daarom gebruikt:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File ..\backend\scripts\run-integrations.ps1
```

Hij gebruikt per run `dnd_portal_test_run_<PID>`, controleert poort 3307, voert migraties tweemaal uit, maakt een unieke admin aan, start één eigen PHP-proces op 8099 en voert backend- en browserchecks uit. Het bestaande databasevolume wordt niet verwijderd.

## Uitgevoerd

De eerdere regels hieronder zijn historische tussenstanden en worden overschreven door de definitieve run in de volgende sectie. Alleen de definitieve run geldt als actuele status.

- **Geslaagd — PHP syntaxcontrole:** alle PHP-bestanden met `php -l`.
- **Geslaagd — frontend formatter:** `pnpm format:check`.
- **Geslaagd — Svelte-check:** `pnpm check`.
- **Geslaagd — bestaande unit tests:** `pnpm test`.
- **Gefaald/geblokkeerd — `pnpm test:browser`:** de frontend startte, maar requests naar `/api/v1/users` en `/api/v1/users/13` kregen `ECONNREFUSED 127.0.0.1:8099`; de backend was niet gestart. De echte-authtest werd zonder credentials overgeslagen.
- **Geslaagd — frontend build:** `pnpm build`.
- **Geslaagd — statische secretcontrole:** geen echte `.env`, private keys of nieuwe credentials toegevoegd.
- **Geslaagd — database-healthcheck:** TCP `127.0.0.1:3307` bereikbaar; PHP las `backend/.env` met `APP_ENV=testing`, `DB_HOST=127.0.0.1`, `DB_PORT=3307` en `DB_NAME=dnd_portal_test`.
- **Geslaagd — migraties:** `php backend/scripts/migrate.php` gaf `SKIP 001_initial_portal_schema`, `APPLY 002_seed_auth_roles`, `APPLY 003_campaign_lifecycle`, `APPLY 004_polls_payments`.
- **Geslaagd — migratie-idempotentie:** opnieuw uitvoeren gaf `SKIP` voor alle vier migraties.
- **Geslaagd — eerste admin:** interactieve PowerShell `Read-Host -AsSecureString` uitgevoerd; account aangemaakt.
- **Geslaagd — backendtestharnas:** `php backend/tests/integration/auth_flow.php` — 4/4 checks.
- **Geslaagd — HTTP-backendtests:** met draaiende PHP-server en `TEST_ADMIN_*` uitsluitend via procesomgeving: CSRF-token, verkeerde Origin, ontbrekende CSRF, login, current-user allowlist, user-list allowlist en aangepaste resource-ID — alle 7 scenario’s geslaagd.
- **Geslaagd — echte browserlogin/logout:** `pnpm exec playwright test tests/browser/auth-real.spec.ts` — 1/1 geslaagd. De test gebruikt formulierinvulling en knopklik, geen directe API-login; `networkidle` voorkomt de eerdere hydration-race. CSRF-cache wordt na sessierotatie geïnvalideerd.

## Definitieve aanvullende run — 2026-10-26

Commando: `powershell -NoProfile -ExecutionPolicy Bypass -File backend/scripts/run-integrations.ps1`.
Database: `dnd_portal_test_run_3999620261026142406`; nieuw en geïsoleerd. Bestaande databases en Docker-volume zijn behouden.

- Geslaagd — MariaDB-healthcheck op `127.0.0.1:3307`.
- Geslaagd — vier migraties vanaf nul (`APPLY`), daarna opnieuw (`SKIP` voor alle vier).
- Geslaagd — eerste admin met afgeschermde `Read-Host -AsSecureString`-invoer.
- Geslaagd — SQL-authbasis: 4/4.
- Geslaagd — HTTP-auth: CSRF-token, verkeerde Origin, ontbrekende CSRF, login, allowlists en resource-ID-autorisatie.
- Geslaagd — aanvullende authmatrix: gebruikte invite, verlopen reset, ingetrokken reset, unieke identifier, sessie-intrekking, rate limit en echte overlappende codeconsumptie: 7/7.
- Geslaagd — self-demotion (`409 last_admin`) en self-disable (`403 forbidden`) via echte HTTP-endpoints.
- Geslaagd — laatste-adminconcurrency: twee afzonderlijke PHP-processen met overlappende transacties; één mutatie slaagt, één wordt geweigerd en er blijft exact minimaal één actieve admin over.
- Geslaagd — volledige laatste-adminmatrix op `dnd_portal_test_run_3980020261026142513`: gelijktijdige demotie/demotie, uitschakelen/uitschakelen en demotie/uitschakelen. Iedere combinatie eindigde met `active_admins=1`; API-uitvoer was telkens één `success` en één `rejected`.
- Geslaagd — zelfdemotie en zelfuitschakeling via HTTP: respectievelijk `409 last_admin` en `403 forbidden`.
- Geslaagd — concurrency: twee afzonderlijke PHP-processen en PDO-verbindingen, gelijktijdig op dezelfde resetrij; exact één consumptie succesvol.
- Geslaagd — browsertest via echte formulieren/knoppen: login/logout, invite genereren, intrekken, vervangen, activeren en bestaande user-edit/reset-flow: 3/3.
- Niet uitgevoerd — afzonderlijke HTTP-versieconflictcase in deze run; de browser user-edit is wel functioneel uitgevoerd.

## Niet lokaal bewezen

- Niet uitgevoerd — InfinityFree runtime-validatie. HTTPS, cookieflags, PHP-extensies, webrootafscherming en productie-DB moeten daar afzonderlijk worden gecontroleerd.
- Niet uitgevoerd — browserconcurrency; de overlap is bewezen op databaseverbindingen/processen, terwijl de PHP development server requestverwerking niet als productieparalleliteit geldt.

PHP-lint is geen runtimevalidatie. Lokale frontendtests zijn geen InfinityFree-tests.
