# Bevindingen

## Huidige architectuur

- **Geslaagd — frontend gevonden:** de actieve app staat onder `player/` en gebruikt Svelte 5, SvelteKit 3, Vite 8, TypeScript en SCSS.
- **Geslaagd — statische richting vastgesteld:** `adapter-auto` is geconfigureerd, maar er is geen gekozen adapter voor InfinityFree. Een build is dus geen bewijs van een passende productie-deployment.
- **Geslaagd — backendgrens vastgelegd:** `backend/public/index.php` is toegevoegd als toekomstige API-frontcontroller. Er is nog geen volledige backend.
- **Niet uitgevoerd — echte `/api/`-proxycontrole:** er is geen live dashboardorigin en geen PHP-hostingtoegang.

## Data en entiteiten

`player/src/lib/database/` bevat browsergeladen mockdata via `getDatabase()`. De aangetroffen entiteiten zijn: `user`, `users`, `campaigns`, `sessions`, `oneShots`, `memberships`, `inviteCodes`, `organizerDashboard`, `paymentRequests`, `paymentRequestUsers`, `activity`, `polls`, `pollResponses` en `availability`.

- **Geslaagd — mockdata geïnventariseerd:** tabellen zijn losse TypeScript-arrays/objecten; er is geen PDO, SQL-schema of migratie.
- **Gefaald — productie-opslag:** gebruikers bevatten onder meer een mockwachtwoord (`test`) en gegevens worden niet server-side opgeslagen.
- **Gefaald — relationele integriteit:** relaties bestaan alleen als IDs in arrays; foreign keys en transacties ontbreken.

## Registratie en login

- **Geslaagd — registratiepagina gevonden:** `/register` controleert een invite-code in mockdata en simuleert accountcreatie.
- **Gefaald — echte login:** de dashboardknop en rolquery (`?role=player`/`?role=dm`) zijn demonstratiegedrag, geen sessie-authenticatie.
- **Niet uitgevoerd — Google/Discord:** bewust buiten scope.

## Tabellen, formulieren en dropdowns

- **Geslaagd — gedeelde adminlaag:** `EntityTable`, `DataTable`, `TableToolbar`, `DropdownSelect`, detailpanelen en selectie-/sorteerlogica zijn aanwezig.
- **Geslaagd — filters/dropdowns:** de admin-entiteiten leveren filteropties; de campaign-switcher en registratieformulier gebruiken lokale componentstate.
- **Gefaald — persistente mutaties:** details zijn read-only; er zijn geen API-formulieren die wijzigingen naar een database schrijven.

## Tests, build en deployment

- **Geslaagd — scripts aanwezig:** `check`, `test`, `test:browser`, `format:check` en `build` staan in `player/package.json`.
- **Geslaagd — lokale frontendvalidatie:** `pnpm check` (0 fouten/waarschuwingen), `pnpm test` (10/10) en `pnpm build` zijn uitgevoerd vanuit `player/`.
- **Gefaald — deploymentconclusie uit build:** de build is lokaal geslaagd, maar bewijst geen InfinityFree-deployment.
- **Niet uitgevoerd — deployment:** er is geen hostingconfiguratie, uploadresultaat of live controle.

## Lokale wijzigingen en backendbestanden

- **Geslaagd — lokale staat behouden:** de checkout bevat ongetrackte directories en een zipbestand; niets daarvan is overschreven of verwijderd.
- **Gefaald — bestaande backend aanwezig:** vóór deze stap bestond in deze checkout geen `backend/`-directory.
- **Geslaagd — credentials niet toegevoegd:** alleen `.env.example` met placeholders is toegevoegd; echte secrets ontbreken.

