# Verificatie

## Lokaal uitgevoerd

- **Geslaagd — formattercheck:** `pnpm format:check`.
- **Geslaagd — Svelte-check:** `pnpm check`, 0 fouten/waarschuwingen.
- **Geslaagd — unit tests:** `pnpm test`, 10/10.
- **Gefaald — browsertests:** `pnpm test:browser`; de suite stopte op bestaande admin browserchecks nadat echte dashboard-API-calls zonder ingelogde sessie de testserver bereikten. Dit is geen productiebugclaim, maar de browserharnasconfiguratie ondersteunt nog geen echte authenticated API fixture.
- **Geslaagd — build:** `pnpm build`.
- **Geslaagd — PHP syntax:** `php -l` op alle PHP-bestanden.
- **Niet uitgevoerd — PHP backendtests:** er is nog geen echte PHP test-suite.
- **Geslaagd — database-healthcheck:** `docker compose -f backend/database/compose.test.yml ps` en container health status `healthy`; MariaDB 11.4 is gebonden aan `127.0.0.1:3307`.
- **Geslaagd — verse migraties:** `powershell -NoProfile -ExecutionPolicy Bypass -File .\backend\scripts\run-integrations.ps1 -KeepDatabase` maakte database `dnd_portal_test_run_4261620261026154112` aan en paste 001 t/m 005 toe.
- **Geslaagd — migratie-idempotentie:** dezelfde runner voerde de tweede ronde uit met `SKIP 001` t/m `SKIP 005`.
- **Niet uitgevoerd — bestaande data:** geen echte productie- of hostingdatabase beschikbaar voor dry-run/migratiecontrole.

## Auditbevindingen

- **Gefaald — mockdata volledig verwijderd:** `player/src/lib/database/tables/` wordt nog door niet-gemigreerde adminroutes geladen; dit mag niet als productiebron worden gebruikt.
- **Gefaald — API-foutgedrag voor alle pagina’s:** niet alle Sveltepagina’s gebruiken de centrale API-client; meerdere routes blijven mockdata laden.
- **Geslaagd — geen plaintext mockwachtwoorden meer in user-detail:** passwordvelden zijn uit de frontend-mocktabellen en user-detailallowlist verwijderd.
- **Geslaagd — geen echte secrets gevonden:** alleen placeholders in `.env.example`; geen `.env`, private key of productiecredential gevonden.
- **Geslaagd — geen nieuwe style-tags/dubbele styles:** bestaande checks passeren; de bestaande SVG inline styles en dynamische kalenderstijl zijn geen nieuwe style-tag of route-override.
- **Niet bewezen — alle private endpoints:** code-inspectie toont auth/CSRF op mutaties, maar zonder runtime-test is dit niet end-to-end bewezen.
- **Niet bewezen — schema/migraties:** SQL is niet tegen een echte MySQL/MariaDB vanaf nul uitgevoerd.
- **Geslaagd — één authsysteem in code:** auth gebruikt `auth_sessions`; er is geen tweede JWT/localStorage-authsysteem toegevoegd.

## Laatste harnasrun

- **Geslaagd:** auth backendflows, CSRF/Origin, allowlists, last-admin, reset concurrency, rate limiting en campagne restore/reload/version-conflict.
- **Gefaald:** `tests/browser/auth-real.spec.ts`: login/logout slaagde, maar invite generation en user-edit/reset time-outten omdat de betreffende UI-routes nog mockgebonden zijn en geen echte backendacties aanbieden.
- **Diagnose verfijnd:** de invite-UI had na navigatie een hydrationrace: de DOM-input bevatte de ingevulde email, terwijl de Svelte-state nog leeg was; de submitknop bleef daardoor disabled en er werd geen request verstuurd. De userlijsttest gebruikte bovendien hardcoded `/admin/users/1`; de echte `GET /api/v1/users` gaf in die run `403 forbidden`, waardoor geen detailroute werd bereikt. Requestdiagnostiek is toegevoegd zonder tokens/wachtwoorden; responsebody bevatte geen secrets.
- **Reparatie in uitvoering:** invite gebruikt nu een echte form-submit met `type=submit` en de browserflow wacht op `data-hydrated=true`; userlijstcellen bevatten echte detail-links en de test haalt het ID uit de lijst.
- **Logs:** runner bewaarde logs onder `%TEMP%\dnd-portal-integrations-42616` en backendlogs onder `%TEMP%\dnd-backend-4261620261026154112.*`.

### Actuele reparatieronde 2026-10-26

- **Geslaagd — statische reparatie:** het inviteformulier heeft nu één submitpad via `onsubmit`; de dubbele `onclick`-submit is verwijderd en `generate()` weigert dubbele uitgifte terwijl een request loopt.
- **Geslaagd — frontendchecks na reparatie:** `pnpm format:check`, `pnpm check` (0 fouten/waarschuwingen) en `pnpm build`.
- **Geblokkeerd — volledige harnasrun:** `powershell -NoProfile -ExecutionPolicy Bypass -File .\backend\scripts\run-integrations.ps1` stopte vóór databasecreatie met `Test database port 3307 is unavailable`. De voorafgaande composecontrole faalde omdat Docker Desktop niet draait (`dockerDesktopLinuxEngine` pipe ontbreekt). Daardoor zijn in deze ronde geen nieuwe browserrequests of `/auth/me`-diagnostiek beschikbaar.
- **Niet geclaimd:** de 403-oorzaak en de volledige drie browserflows zijn in deze ronde niet functioneel opnieuw bewezen; bestaande diagnostiek blijft gelden totdat de runner met een beschikbare MariaDB opnieuw is uitgevoerd.

### Definitieve herstelrun 2026-10-26

Commando: `powershell -NoProfile -ExecutionPolicy Bypass -File .\backend\scripts\run-integrations.ps1`

- **Geslaagd — MariaDB:** healthcheck gezond op `127.0.0.1:3307`.
- **Geslaagd — migraties:** nieuwe database `dnd_portal_test_run_3188020261026193056`; migraties 001 t/m 005 toegepast; tweede ronde gaf uitsluitend `SKIP`.
- **Geslaagd — backend:** auth, CSRF/Origin, last-admin, reset-concurrency, rate limiting en campaign restore.
- **Geslaagd — browser:** `3 passed (10.1s)` met echte PHP-backend en echte formulieren: login/logout, invite uitgifte/vervanging/activatie en user-edit/reset/opnieuw inloggen.
- **Bewezen 403-oorzaak:** `admin_concurrency_matrix.php` verwijderde de rol `portal_admin` van de gedeelde runner-admin en herstelde die niet. `/auth/me` was 200 maar retourneerde `roles: []`; `GET /api/v1/users` wees daarom correct af met 403. De runner voert de matrix nu vóór de herstellende `admin_concurrency.php` uit.
- **Bewezen invitebevinding:** browserdiagnostiek liet zien dat de knop na klik busy/disabled werd; de echte submit was gestart. De test las direct daarna, vóór responseverwerking, naar `Code:` en registreerde daardoor ten onrechte geen resultaat. De test wacht nu op de concrete zichtbare code-status. De applicatie gebruikt één submitpad en voorkomt dubbele uitgifte tijdens busy.
- **Logs:** `%TEMP%\dnd-portal-integrations-3188020261026193056`; backendlogs `%TEMP%\dnd-backend-3188020261026193056.*`.

## Hostingstatus

- **Niet uitgevoerd — InfinityFree:** routing, PHP-versie, cookies, HTTPS, webrootisolatie, MySQL-variant en upload zijn niet gecontroleerd.
- **Niet uitgevoerd — tijdelijke diagnose:** de oude hostingcheck is niet op echte hosting opgeschoond/gevalideerd.

## Matrix-isolatie en memberships

- **Geslaagd — zelfstandige admin concurrency matrix:** alle drie overlappende scenario's slaagden met eigen admins en eigen fixtures; de runner-admin bleef ongemoeid.
- **Geslaagd — volledige runner na matrixwijziging:** migraties, backendregressies en de drie auth-browserflows slaagden opnieuw (`3 passed`).
- **Niet geslaagd — memberships browserflow:** echte `POST /api/v1/campaigns/1/members` retourneerde 201, maar het daaropvolgende echte `GET /api/v1/campaigns/1/members` retourneerde `{"data":[]}`. Dit is niet als functioneel geslaagd geclaimd.
- **Geslaagd — frontendchecks:** `pnpm format:check` en `pnpm check` zonder fouten of waarschuwingen.
- **Geslaagd — sessies/deelnemers:** runner `dnd_portal_test_run_3831620261026200331` testte sessiecreatie, sessielijst, deelnemerslijst, afzonderlijke RSVP/attendance en annulering met behoud van historie; de echte browserregressies bleven groen (`4 passed`).
- **Hersteld — ID-parsing:** subroutes voor campaign sessions en session participants gebruikten `basename()` en zijn omgezet naar expliciete padsegmenten.
- **Geslaagd — resterende sessie/beschikbaarheid:** runner `dnd_portal_test_run_3857220261026201004` testte sessies, RSVP/attendance, annulering, beschikbaarheid opslaan/herladen/verwijderen en alle bestaande auth/membership/browserregressies; browser `4 passed (18.2s)`.
- **Geslaagd — poll backend:** runner `dnd_portal_test_run_3788020261026201405` testte poll create/get, deelnemerstoewijzing, single vote, resultaten, single-choice-validatie, sluiten en stemweigering na sluiten.
- **Hersteld — poll ID-parsing:** campaign-poll-, vote- en results-subroutes gebruikten `basename()`; deze gebruiken nu expliciete resource-ID-segmenten.
- **Niet uitgevoerd:** gevraagde poll-speler-browserflow, multiple-choice-browserflow en afzonderlijke poll-rechtenmatrix.
- **Geslaagd — poll UI statisch:** poll-detailpagina ondersteunt single/multiple choice, opgeslagen keuzes via `selected_option_ids`, saving/success/error-status en gesloten pollweergave; `pnpm format:check`, `pnpm check` en `pnpm build` slagen.
- **Niet uitgevoerd:** de nieuwe poll-speler-browserflow is nog niet aan `run-integrations.ps1` toegevoegd en daarom niet functioneel geclaimd.
- **Geslaagd — memberships herstel:** runner `dnd_portal_test_run_3212420261026195511` rapporteerde POST→database→GET en de echte browserflow; Playwright `4 passed (15.4s)`.
- **Bewezen oorzaak:** GET `/campaigns/{id}/members` gebruikte `basename()` en gaf daardoor campagne-ID `0` door. De POST gebruikte wel het juiste segment.

# Lokale handmatige startcontrole (2026-10-26)

- **Lokaal functioneel geslaagd:** `docker compose -f backend/database/compose.test.yml up -d`; bestaande MariaDB 11.4-container was healthy en bleef behouden.
- **Lokaal functioneel geslaagd:** aparte `dnd_portal_development` aangemaakt; bestaande `dnd_portal_test`/volume niet gewist.
- **Lokaal functioneel geslaagd:** `php backend/scripts/migrate.php` paste alle migraties toe en een volgende run gaf `SKIP 001_initial_portal_schema` t/m `SKIP 005_payment_corrections`.
- **Lokaal functioneel geslaagd:** eerste lokale admin `devadmin` bestaat in de ontwikkeldatabase als actieve `portal_admin`; het wachtwoord is alleen interactief ingevoerd en niet gedocumenteerd.
- **Lokaal functioneel geslaagd:** `& .\backend\scripts\start-local.ps1` start PHP met `backend/router.php` op `127.0.0.1:8099` en Vite op `127.0.0.1:5173`.
- **Lokaal functioneel geslaagd:** via de echte Vite-proxy gaf CSRF `200`, login `200` en `/api/v1/auth/me` `200`; de response bevatte alleen profielvelden en `portal_admin`.
- **Lokaal statisch gecontroleerd:** `backend/.env.local` wordt door `backend/.gitignore` genegeerd. De lokale origin is exact `http://127.0.0.1:5173`; `localhost` is niet toegestaan.
- **Lokaal statisch gecontroleerd:** het loginformulier gebruikt één submitpad (`form` + `type=submit`) en de bestaande externe SCSS bevat expliciete inputstijlen; geen style-tag toegevoegd.
- **Niet uitgevoerd:** InfinityFree-validatie van HTTPS, Secure cookies, PHP-extensies, routing en private bestandstoegang.
- **Openstaand:** de echte poll-browserflows blijven openstaand zoals in `docs/backend/15-polls/README.md`; deze lokale-startverbetering claimt die niet als geslaagd.
# Gerichte toegangscontrole adminroutes (2026-10-26)

- **Oorzaak vastgesteld:** de statische admin-layout rendeerde de browserveilige mockdatabase direct tijdens route-navigatie, vóórdat `/api/v1/auth/me` was gecontroleerd. Dit maakte het admin-skelet en mockinhoud rechtstreeks zichtbaar; de PHP-API zelf bleef server-side autoriseren.
- **Hersteld:** `player/src/routes/admin/+layout.svelte` toont eerst uitsluitend een neutrale laadstatus. Alleen een actuele sessie met `portal_admin` rendert de admin-childroute; zonder sessie of met onvoldoende rol wordt geen admininhoud gerenderd.
- **Backend lokaal functioneel geslaagd:** zonder sessie geven `GET /api/v1/users`, `GET /api/v1/users/1` en `GET /api/v1/dashboard` HTTP 401.
- **Backend lokaal functioneel geslaagd:** een geïsoleerde gewone speler krijgt HTTP 403 op gebruikerslijst en gebruikersdetail.
- **Browser lokaal functioneel geslaagd:** nieuwe context zonder cookies/localStorage/sessionStorage gaf bij directe navigatie naar `/admin/users` en `/admin/users/1` alleen `Admin access required`; herladen gaf hetzelfde.
- **Browser lokaal functioneel geslaagd:** hetzelfde browserregressiescenario controleerde `GET /api/v1/users` op HTTP 401 en `Cache-Control: no-store`.
- **Gehele runner lokaal functioneel geslaagd:** migraties vanaf nul en tweede `SKIP`-ronde; backendregressies; `6 passed` browsertests.
- **Niet uitgevoerd:** InfinityFree-validatie. Dit blijft afzonderlijk open voor HTTPS, cookies, private bestandstoegang en statische deployment.
# Aanvulling betalingen

- Lokale betaalbackend en browserflow: geslaagd op een verse geïsoleerde MariaDB-testdatabase.
- Getest: verzoek, verplichting, deelbetaling, restbetaling, reversal, saldo, eigen spelerprivacy en geweigerde speler-mutatie.
- `pnpm check`, build en PHP-lint: geslaagd.
- InfinityFree-validatie: niet uitgevoerd.

# Aanvulling betalingen en dashboard (2026-10-26)

- Migraties 001–007 vanaf nul en een tweede ronde met uitsluitend `SKIP`: geslaagd.
- Duplicate payment reference: HTTP 409 en directe databasecontrole: geslaagd.
- Dashboardfixture met deelbetaling en reversal: speler zag uitsluitend 1000 cent eigen openstaand; admin zag beheertotalen en activiteit: geslaagd via API.
- `pnpm check`, `pnpm build` en PHP-lint: geslaagd.
- Gecombineerde browserrun: 8 tests geslaagd; één bestaande poll-browserfixture faalde vóór login. De afzonderlijke betaalbrowserflow slaagde.
- Geen InfinityFree-validatie uitgevoerd.

# Definitieve aanvulling betalingen/dashboard (2026-10-26)

- Werkelijke overlappende payment-processen: duplicate reference, reversal race en payment/correction race gecontroleerd tegen MariaDB: geslaagd.
- Browsergroepen: auth/access 6/6, polls 2/2, payment/dashboard 2/2: geslaagd.
- De eerdere pollloginfout was testbatch-rate-limitvervuiling, niet een authenticatie- of fixture-rolfout. Browsergroepen zijn onafhankelijk gemaakt met testfixture-reset.
- Frontendcheck, build en PHP-lint: geslaagd.
- InfinityFree-validatie: niet uitgevoerd.

