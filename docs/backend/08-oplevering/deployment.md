# Reproduceerbare deploymentprocedure

## 1. Voorbereiden

1. Maak een releasekopie vanuit een schone checkout. Upload geen `node_modules/`, `.svelte-kit/`, tests, SQL-backups, `.env` of zipbestanden.
2. Zet `APP_ENV=production`, de echte HTTPS-dashboardorigin en unieke databasegegevens uitsluitend in de serveromgeving.
3. Maak lokaal een statische frontendbuild met `pnpm build` nadat de API-integratie en mockdata-gating zijn afgerond. Gebruik geen SvelteKit-serveradapter op InfinityFree.

## 2. Webroot

De exacte InfinityFree-webroot moet eerst handmatig worden bevestigd. Publiek: uitsluitend statische frontendbestanden en de PHP-frontcontroller/API-entrypoint. Privé: `backend/src/`, `.env`, `backend/database/migrations/`, tests en scripts. Als InfinityFree geen aparte webroot buiten de accountmap ondersteunt, gebruik expliciete webserverregels die deze paden blokkeren en test ieder pad via HTTPS met status 403/404.

## Reproduceerbaar lokaal releasepakket

Voer vanuit de repository-root uit:

```powershell
pnpm --dir player build
New-Item -ItemType Directory -Force -Path .release\frontend | Out-Null
Copy-Item -Recurse -Force player\build\* .release\frontend\
New-Item -ItemType Directory -Force -Path .release\api | Out-Null
Copy-Item -Recurse -Force backend\public\* .release\api\
```

Upload na handmatige controle alleen de statische frontendinhoud en de publieke API-entrypoint/`.htaccess` naar de bevestigde webroot. Upload nooit `backend/src`, `.env`, migrations, tests, backups, `node_modules`, `.svelte-kit` of lokale releaseartefacten. Als PHP geen veilige private map buiten de webroot biedt, plaats interne code alleen achter expliciete deny-regels en bewijs dat met HTTPS-403/404-controles.

## 3. Database

1. Maak een backup volgens [backup-herstel.md](backup-herstel.md).
2. Voer `001_initial_portal_schema.sql` t/m `004_polls_payments.sql` uit in phpMyAdmin of een gecontroleerde operatorverbinding, nooit via HTTP.
3. Controleer foreign keys, InnoDB, charset/collation en `schema_migrations`.
4. Migreer bestaande data via een read-only dry-run; stop bij collisions of ontbrekende relaties.

## 4. Eerste admin

Maak eerst een bestaand profiel met status `invited`, genereer een invite via een geautoriseerde beheeractie en activeer het profiel met een uniek wachtwoord. Er is geen standaardwachtwoord en geen publiek installatiescript. Ken daarna uitsluitend de `portal_admin`-rol toe via een operatoractie/SQL-procedure met backup en audit.

## 5. Smoke test en cleanup

Test HTTPS, Secure/HttpOnly/SameSite-cookie, `/api/v1/health`, CSRF, login, logout, `/me`, disabled account, invite/reset en een geautoriseerde/ongeautoriseerde resource-ID. Zet tijdelijke diagnostiek uit, verwijder de hostingcheck en roteer tijdelijke tokens. Controleer daarna dat `.env`, SQL en source niet via HTTP bereikbaar zijn.

# Lokale handmatige ontwikkelstart

De integratietestrunner blijft de geïsoleerde, tijdelijke testdatabases gebruiken. Voor handmatig bekijken en testen is er daarnaast één blijvende lokale ontwikkelomgeving:

```powershell
& .\backend\scripts\start-local.ps1
```

Het commando bepaalt zijn paden vanuit de scriptlocatie en doet het volgende:

- start `backend/database/compose.test.yml` zonder een volume te verwijderen en wacht op de Compose-healthstatus `healthy`;
- gebruikt uitsluitend de aparte database `dnd_portal_development` op `127.0.0.1:3307`;
- maakt `backend/.env.local` eenmalig aan met lokale Compose-credentials; dit bestand wordt door `backend/.gitignore` genegeerd;
- past migraties idempotent toe en meldt bij een volgende run `SKIP`;
- controleert of de opgegeven actieve portal-admin bestaat, en vraagt alleen bij een ontbrekende admin een verborgen wachtwoord in;
- start PHP op `127.0.0.1:8099` met `backend/router.php` en Vite op `127.0.0.1:5173` met `/api`-proxy naar die PHP-server;
- wacht op beide luisterpoorten en print de bruikbare URL en het gekozen loginidentifier.

Open exact `http://127.0.0.1:5173/`; gebruik niet `http://localhost:5173/`. De backend accepteert lokaal uitsluitend `APP_ALLOWED_ORIGIN=http://127.0.0.1:5173`. Cookies zijn lokaal `HttpOnly`, `SameSite=Lax` en niet `Secure` omdat dit HTTP is; productie moet HTTPS gebruiken en `Secure` inschakelen via `APP_ENV=production`.

De scriptprompt gebruikt geen standaardwachtwoord en zet het wachtwoord niet als commandoregelargument. De lokale admingegevens worden niet uit tijdelijke integratiefixtures overgenomen. Stoppen met Ctrl+C stopt alleen de door het script gestarte processen; een al bezette poort wordt geweigerd zodat geen oud backendproces wordt gebruikt. Stop de bestaande eigenaar expliciet als de preflightmelding dat vereist.

De testdatabase/container en het bestaande volume worden niet verwijderd. Voor een lokale ontwikkelreset bestaat bewust geen resetcommando; verwijdering van lokale data moet een afzonderlijke, expliciete operatorhandeling zijn.

## Handmatige controle

1. Start het script vanuit de repositoryroot of een andere werkdirectory.
2. Controleer in de output de URL `http://127.0.0.1:5173/` en het loginidentifier.
3. Log in via het formulier. Een request vanaf `localhost` hoort door de exacte origincontrole te worden geweigerd.
4. Controleer desgewenst `backend-local-error.log` en `frontend-local-error.log`; deze bestanden zijn lokale runtime-output en worden niet geüpload.
5. Stop met Ctrl+C. Laat de MariaDB-container draaien voor de volgende lokale run.

InfinityFree-validatie is hiermee niet uitgevoerd. Daar moeten HTTPS, `Secure` cookies, PHP-extensies, routing en private bestandstoegang afzonderlijk worden gecontroleerd.
