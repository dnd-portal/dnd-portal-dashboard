# D&D Portal Player — werkwijze voor code-agents

Deze instructies gelden voor alles binnen `player/`. Lees ze vóór wijzigingen. Behoud ook de repository-instructies uit bovenliggende `AGENTS.md`-bestanden. Dit pakket is het SvelteKit-playerprototype; de PHP/API-instructies van de hoofdrepository blijven van toepassing zodra een feature op die backend wordt aangesloten.

## Eerst begrijpen, daarna aanpassen

- Inspecteer de bestaande route, gedeelde componenten, datalaag en relevante SCSS voordat je code toevoegt.
- Hergebruik een bestaande verantwoordelijkheid. Maak geen tweede tabel, header, toolbar of actiemenu omdat een pagina andere data toont.
- Respecteer wijzigingen van de gebruiker. Geen resets, massale overschrijvingen of wijzigingen aan niet-gerelateerde pagina's.
- Bij een layoutfout: inspecteer de CSS-cascade en computed styles. Noem de veroorzakende selector en herstel de bron; stapel geen overrides op het symptoom.

## Architectuur en eigenaarschap

| Locatie                           | Verantwoordelijkheid                                                                                            |
| --------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| `src/routes/admin/+layout.ts`     | Eén mockdata-load voor de adminrouteboom.                                                                       |
| `src/routes/admin/+layout.svelte` | Applicatieshell, navigatie, mobiele bediening en documenttitel.                                                 |
| `src/lib/admin/context.ts`        | Request-/componentboomgebonden toegang tot admingegevens. Geen gedeelde mutable gebruikersdata op moduleniveau. |
| `src/lib/admin/entities.ts`       | Typed tabelkolommen, data-adapters, filteropties en pure filtering.                                             |
| `src/lib/admin/details.ts`        | Typed detailpresentatiemodellen en correcte relaties/links.                                                     |
| `src/lib/admin/navigation.ts`     | Navigatiesecties en routeresolutie.                                                                             |
| `src/lib/adminPageMeta.ts`        | De vastgelegde overview-headerteksten.                                                                          |
| `src/lib/components/admin/`       | Herbruikbare admincompositie.                                                                                   |
| `src/lib/components/shared/`      | Kleine componenten die ook buiten admin bruikbaar zijn.                                                         |
| `src/lib/styles/layouts/`         | Applicatie-indeling en buitenste paginaruimte.                                                                  |
| `src/lib/styles/pages/`           | Unieke paginacompositie; geen gekopieerde componentstyling.                                                     |
| `src/lib/styles/components/`      | Styling van de corresponderende componenten, gegroepeerd per domein.                                            |

De admin-layout laadt de browserveilige mockdatabase één keer via de universele layout-load. Introduceer geen tweede `onMount(getDatabase)` per admincomponent. Importeer nooit een echte serverdatabase-driver in universele of browsercode. Bij API-integratie: vervang de querygrens en behoud de presentatiecomponenten.

Het prototype heeft geen productie-authenticatie of persistente adminbewerkingen. Doe niet alsof rolkeuze in de UI een beveiligingsgrens is. Bouw nieuwe beveiliging/persistentie uitsluitend binnen de expliciete taak en de backendarchitectuur van de repository.

## Componenten gebruiken

- Overzichtsroute: composeer `AdminHeader` en `EntityTable` met een typed entity-key.
- Nieuwe tabel: voeg de dataspecifieke adapter toe in `entities.ts`. Gebruik `DataTable<Row>` voor tabelstructuur, selectie en lege toestand, met een typed Svelte-snippet voor eigen cellen.
- Voeg geen entity-afhankelijke `if users / else campaigns / ...`-keten toe aan `DataTable`.
- Gebruik `TableToolbar` voor zoeken, filters, reset en telling. Houd URL-queryparameters intact via de bestaande SvelteKit-navigatie-API.
- Gebruik `RowActions` voor actiemenu's, `StatusLabel` voor statussen, `UserIdentity` voor avatars/namen en `Popover` voor aanvullende gegevens.
- Gebruik `DetailPanel`, `DefinitionList` en `AdminDetail` voor herhaalde detailopbouw. Een werkelijk afwijkende pagina mag deze onderdelen zelf componeren.
- `DropdownSelect` is de centrale component voor custom single-selectvelden. Wijzig uiterlijk en bediening in de gedeelde componentbestanden. Voeg geen lokale kopieën of routegebonden dropdownoverrides toe.
- Tabelsortering en de bijbehorende styling worden uitsluitend aangepast via de bestaande gedeelde tabelimplementatie.
- `AttendanceCalendar` blijft gespecialiseerd; forceer een kalender niet in de generieke tabel.
- Maak geen component voor ieder los HTML-element. Extraheer herhaalde structuur, gedrag of een duidelijk zelfstandig onderdeel.
- Gebruik typed props en Svelte 5-snippets. Geen `any`, `@ts-ignore` of uitgeschakelde checks om typefouten te verbergen.

### Interactie-afspraken

- Zoeken en filters moeten gecombineerd werken. Vernieuwen en terug/vooruit moeten de URL-weergave behouden.
- Select-all betreft zichtbare rijen; verborgen selecties blijven behouden totdat de gebruiker ze wist. De indeterminate-state betreft uitsluitend de zichtbare subset.
- Geen nepbulkacties. Toon pas een muterende actie als de bestaande backend/API die ondersteunt en rechten controleert.
- Geen "Edit"-link naar een leespagina. Bied bestaande, correct benoemde acties aan.
- Menu's en popovers moeten met toetsenbord, Escape, buitenklikken en focusherstel werken. Gebruik de native top layer om clipping door tabelscroll te voorkomen.
- Data-adapters mogen de dataset niet muteren. Gebruik echte records en expliciete lege-/niet-gevonden-toestanden, geen fictieve metrics.

## Styling — harde afspraken

1. **Geen `<style>`-tags in Svelte**, ook geen `<style lang="scss">`.
2. **Geen statische `style`-attributen of statische `style:`-directives.** Alleen werkelijk dynamische waarden, bijvoorbeeld de kalender-heatmap via `--unavailable`, mogen een gedocumenteerde CSS-custom-property gebruiken.
3. Alle styling staat in externe SCSS. Gebruik BEM-namen en nesting (`&__element`, `&--modifier`, states en mediaqueries bij de eigenaar).
4. `main.scss` is het enige stylesheet-entrypoint en wordt eenmaal in de rootlayout geïmporteerd. Geen extra component-/routeimports die dezelfde CSS opnieuw uitsturen.
5. Gebruik `@use` voor Sass-modules. De externe Google Fonts-CSS-URL in `_base.scss` is fontloading, geen Sass-`@import`-module.
6. Reset, bodyachtergrond, box-sizing (inclusief pseudo-elementen), fontloading en globale focushelpers staan alleen in `_base.scss`.
7. Gebruik centrale tokens uit `_variables.scss` voor gedeelde kleuren, fonts en geometrie. Voeg betekenisvolle tokens toe waar nodig; geen tweede thema of eindeloze willekeurige palettevariabelen.
8. Geen ongescopete componentselectors zoals `h1`, `header`, `table`, `input`, `.card`, `.grid` of `.eyebrow` op globaal niveau. Routegebonden selectors mogen niet naar andere schermen lekken.
9. **Geen `:global(...)` in externe SCSS.** Dat is Svelte-stylebloksyntax en hoort niet in deze architectuur.
10. **Geen `_legacy.scss` of vervangend catch-allbestand.** Verwijder vervangen CSS en imports tijdens iedere migratie.
11. Geen onnodige `!important`, negatieve margin-correcties of `overflow-x: hidden` op document/layout om fouten te maskeren. Functionele overflow, zoals een horizontaal scrollende tabel, is toegestaan.
12. De adminlayout beheert één keer buitenpadding en maximale contentbreedte. Cards, headers en tabellen voegen geen tweede paginacontainer toe. Gebruik `min-width: 0` en `minmax(0, 1fr)` waar inhoud moet kunnen krimpen.
13. Houd de officiële branding, Atkinson Hyperlegible en blauwe accenten. Geen groen als merkaccent. Functionele statuskleuren zijn toegestaan.
14. Iedere adminpagina krijgt een eyebrow, titel en description via de gedeelde header. De titel-line-height blijft **0.95**, dus minder dan 1. Houd spacing compact en voorkom clipping bij meerregelige titels.
15. Wijzig de afgesproken headerteksten niet als bijeffect van technische werkzaamheden.

De bestaande player- en registratiepagina's zijn gescoped onder hun eigen root. Hun oude interne classnamen zijn behouden voor compatibiliteit; nieuwe uitbreidingen gebruiken betekenisvolle BEM-namen en componenten. Dit is geen vrijbrief om nieuwe generieke CSS toe te voegen.

## Formatting

- Vier **spaties**, geen tabs; `tabWidth: 4`, `useTabs: false`.
- Laat Prettier de markup, TypeScript en SCSS formatteren. Geen samengeperste volledige pagina op één regel en geen handmatige formatter-omwegen.
- Behoud de bestaande formatter- en TypeScriptconfiguratie tenzij de taak een wijziging vereist.
- `#lib/*` verwijst via package imports naar `src/lib/*`. Gebruik `.js` voor TypeScript-moduleimports en `.svelte` voor componentimports, volgens de bestaande resolutie.

## Verificatie

Voer vanuit `player/` uit:

```sh
pnpm format:check
pnpm check
pnpm test
pnpm test:browser
pnpm build
```

- Installeer voor de eerste browsercontrole Chromium met `pnpm exec playwright install chromium`.
- Tests staan onder `tests/`; test gedeeld gedrag zoals filtering, selectie, relaties en routeresolutie.
- Controleer gewijzigde schermen in de browser op desktop en mobiel. Bij gedeelde CSS ook registratie, player en admin heen en terug bezoeken.
- Controleer runtime-/hydrationfouten, documenttitels, uitlijning, horizontale overflow, menu's, filters en focusbediening.
- Bewijs een layoutfix met computed styles/metingen. Alleen een succesvolle build bewijst geen visuele juistheid.
- Pas afhankelijkheden of build-scripttoestemming niet breed aan om een omgevingsprobleem te omzeilen. Meld een blokkade concreet.
- Vermeld exact wat werkelijk is uitgevoerd, resultaten en beperkingen. Zeg nooit "volledig afgerond" als migratie of controle nog openstaat.

## Niet doen

- Geen productiedata, credentials, `.env`, browserlogs met gevoelige gegevens of `node_modules` in overdrachtsarchieven.
- Geen mockdata als echte productiegegevens presenteren.
- Geen grote dependency of framework toevoegen voor een kleine UI-helper zonder concrete noodzaak.
- Geen niet-werkende UI, verborgen bestaande functionaliteit of uitgeschakelde tests als oplossing.
- Geen ongevraagde deployment, database-reset of wijziging aan de PHP-authenticatie.
