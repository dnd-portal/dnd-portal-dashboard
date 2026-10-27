# D&D Portal Player Dashboard

SvelteKit 3 / Svelte 5-prototype met een player-/organizerdashboard, registratie en een adminomgeving. Lees `AGENTS.md` voordat je wijzigingen maakt.

## Starten

Gebruik een actuele Node-versie die aan de package-engines van Vite voldoet en pnpm. De refactor is gecontroleerd met Node 24 en pnpm 11.

```sh
pnpm install --frozen-lockfile
pnpm dev
```

Er is voor dit prototype geen PHP-server, MySQL of `.env` nodig. `src/lib/database/` bevat de meegeleverde geanonimiseerde mockdata. Registratie en rolkeuze zijn demonstratiegedrag, geen productie-authenticatie. De PHP/API-architectuur van de hoofdrepository blijft de beoogde grens voor echte authenticatie en databasebewerkingen.

## Controles

```sh
pnpm format:check
pnpm check
pnpm test
pnpm build
```

De acht Node-tests laden de pure admin-adapters met Vite en controleren filtering, selectie, detailrelaties, navigatie en het behouden van de dataset. Ze vereisen geen browserinstallatie.

Voor de zeven browserregressietests:

```sh
pnpm exec playwright install chromium
pnpm test:browser
```

Playwright start zelf een lokale Vite-server op `127.0.0.1:5173`. De tests controleren desktop/mobiel, uitlijning, overflow, refresh/back bij filters, selectie, menu's, breadcrumbs, kalenderbediening en CSS-isolatie. Optioneel kan `PLAYWRIGHT_CHROMIUM_EXECUTABLE` naar een bestaande Chromium/headless-shell executable verwijzen. Normaal is dat niet nodig.

Formatteren gebeurt met vier spaties. `pnpm format` past de bestaande formatterconfiguratie toe op broncode, tests en projectdocumentatie.

## Adminstructuur

`routes/admin/+layout.ts` laadt de mockdatabase voor de routeboom. De adminlayout geeft toegang via een request-/componentboomgebonden context; componenten doen geen eigen database-loads meer.

- `adminPageMeta.ts`: overviewteksten voor eyebrow, titel en description.
- `admin/navigation.ts`: navigatie en herkenning van de sectie, ook op detailroutes.
- `admin/entities.ts`: typed kolommen, cellen, filteropties en table-adapters.
- `admin/details.ts`: detailpresentatie, veldlijsten en relaties.
- `admin/selection.ts`: pure logica voor zichtbare selecties.
- `components/admin/`: compositie, tabellen, headers, panels en kalender.
- `components/shared/`: statuslabels, identiteit, lege toestanden, popovers en actiemenu's.

### Een overzicht toevoegen

1. Definieer de route en teksten in `adminPageMeta.ts`.
2. Voeg de navigatielink toe in `admin/navigation.ts`.
3. Definieer de typed kolommen, rows en eventuele filtervelden in `admin/entities.ts`.
4. Composeer `AdminHeader` en `EntityTable` in de route. Kijk naar `routes/admin/campaigns/+page.svelte`.

Pas de generieke `DataTable` niet aan met een nieuwe entity-`if`-tak. Voor een afwijkende tabel kun je de tabelbasis direct met typed cell-/toolbar-/actions-snippets gebruiken.

### Een detailweergave toevoegen

Voeg het presentatiemodel toe aan `admin/details.ts` en composeer `AdminDetail` in de detailroute. Voor unieke inhoud kun je `AdminHeader`, `DetailPanel`, `DefinitionList` en `EntityCell` direct combineren.

De bestaande detailroutes zijn read-only. Daarom bieden de rijmenu's alleen "View details" aan. Selectie biedt alleen "Clear selection" totdat een echte bulkactie beschikbaar is; er wordt geen niet-bestaande bewerk- of verwijderfunctie gesuggereerd.

### URL-filters

De queryparameters `q`, `role`, `status` en eventuele andere geconfigureerde filtervelden blijven in de URL. In SvelteKit 3 gebruikt ondiepe navigatie `page.shallow.url`; `EntityTable` leest die URL met `page.url` als fallback. Gebruik de bestaande aanpak als je filters uitbreidt.

## Styling

`routes/+layout.svelte` importeert eenmaal `styles/main.scss`.

```text
styles/
    _base.scss
    _variables.scss
    main.scss
    layouts/
        _admin-layout.scss
    pages/
        admin/_overview.scss
        player/_dashboard.scss
        auth/_register.scss
    components/
        admin/
            _header.scss
            _table.scss
            _detail.scss
            _calendar.scss
        shared/
            _user-identity.scss
            _status-label.scss
            _content-state.scss
            _row-actions.scss
            _popover.scss
```

Geen legacybestand, Svelte-styleblokken of statische inline styling. De kalender gebruikt één dynamische CSS-custom-property voor de heatmap. De adminlayout beheert maximale contentbreedte en buitenpadding. Tabeloverflow blijft in de eigen scroller; native popovers verschijnen in de top layer en worden daardoor niet afgeknipt.

De player- en registratiepagina behouden hun bestaande markup en gedrag. Hun styling is afzonderlijk gescoped en niet via globale overrides verbonden met admin. Bij volgende uitbreidingen kunnen hun interne onderdelen gericht worden opgesplitst; de huidige refactor herschrijft niet ongevraagd de volledige playerflow.

## Bekende grenzen

- Gegevens komen uit de meegeleverde mocktabellen. Er zijn geen echte adminmutaties of productie-authenticatie toegevoegd.
- De fonts worden centraal via Google Fonts geladen. Bij een geblokkeerde verbinding gebruikt de browser de opgegeven fallback-stack.
- CSS anchor positioning plaatst popovers bij hun trigger. Browsers zonder die ondersteuning gebruiken een vaste positie binnen de viewport; de native Popover API is wel nodig.
- Een onbekend record krijgt een expliciete niet-gevonden-weergave. De bestaande mockdetailroutes blijven HTTP 200 teruggeven.
- Adapter-auto kiest nog geen specifieke hostingprovider. Een succesvolle build is geen deployment.
