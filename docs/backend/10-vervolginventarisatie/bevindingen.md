# Bevindingen

## Functieoverzicht

| Functie | Bestaande frontendroute | Databron | Endpoint/rechten | Status | Testdekking | Resterend werk |
|---|---|---|---|---|---|---|
| Campagnes | `/admin/campaigns`, `/admin/one-shots` en detailroutes | Mock via admin-layout | `GET/POST/PATCH /api/v1/campaigns`; lezen lid/admin, schrijven admin of campagne-DM | Backend aanwezig, frontend mock | Alleen backendcode/static; geen functionele domeintest | API-client/loaders aansluiten, memberships en archiveren testen |
| One-shots | `/admin/one-shots`, `/admin/one-shots/[id]` | Mock | Zelfde `campaigns`-endpoint, `type=one_shot`; dezelfde rechten | Schema ondersteunt type, UI niet aangesloten | Geen echte one-shot runtimeflow | API koppelen en type/status/ID-mapping testen |
| Memberships | `/admin/memberships` | Mock | Ontwerp noemt members, maar er is geen actuele publieke membership-route in `index.php`; service heeft `addMember`/`leave` maar route ontbreekt | Niet aangesloten | Geen endpointtest | Concrete routes, allowlists en DM/admin-autorisatie toevoegen |
| Speelsessies | `/admin/sessions`, `/admin/campaigns/[id]`, details | Mock | `GET/POST /campaigns/{id}/sessions`, `PATCH /sessions/{id}`; lid lezen, DM/admin beheren | Backend aanwezig, frontend mock | Geen echte domeinruntime | Centrale client, formulieren en versieconflicten aansluiten |
| RSVP | `/admin/sessions/[id]`, deelnemercomponenten | Mock | `PATCH /sessions/{id}/participants/{userId}`; speler eigen RSVP, DM/admin alle deelnemers | Backend aanwezig, frontend mock | Geen functionele test | UI aansluiten; deadline, vertrek en historische deelnemers testen |
| Aanwezigheid | `/admin/attendance` | Mock | Zelfde participant-endpoint; alleen DM/admin mag `attendance_status` wijzigen | Backend aanwezig | Geen functionele test | UI/API aansluiten en autorisatie testen |
| Beschikbaarheid | Kalender/admin availability-componenten | Mock | `GET /availability`, `PUT /availability/{date}`; gebruiker eigen, admin eventueel beheer | Backend aanwezig, frontend mock | Geen functionele test | API-koppeling en onbekend-vs-onbeschikbaar testen |
| Polls | `/admin/polls`, `/admin/polls/[id]` | Mock | `GET/POST /campaigns/{id}/polls`, `PUT /polls/{id}/vote`; member lezen/stemmen, DM/admin beheren | Backend aanwezig, frontend mock | Alleen code-inspectie; geen echte polltest | Opties/deelnemers endpoints, sluiting en single/multiple runtime testen |
| Betalingen | `/admin/payments`, `/admin/payments/[id]` | Mock | `GET/POST /campaigns/{id}/payment-requests`, `POST /payment-obligations/{id}/entries`, eigen obligations; DM/admin beheer | Backend deels aanwezig, frontend mock | Geen functionele betalingstest | obligations/correcties API en frontend aansluiten; saldo/valuta testen |
| Dashboard | `/` en admin-overzichten | Gemengd: authstatus echt, dashboardinhoud grotendeels mock | `GET /api/v1/dashboard`; ingelogde gebruiker, serverberekende totalen | Endpoint aanwezig, hoofdinterface niet op dashboard-API | Auth browserflow, geen dashboarddataflow | Dashboard-client aansluiten zonder mockfallback; privacy en totalen testen |
| Navigatietotalen | admin-layout/sidebar | Mock via `getDatabase()` in `admin/+layout.ts` | Geen backendendpoint voor totalen | Niet gemigreerd | Geen echte API-test | Per teller bron bepalen; serverquery of expliciete demo-labels, niet ongemerkt mockdata |

## Werkelijke frontendgrens

De echte API-client wordt gebruikt door login/logout/me, registratie, reset, gebruikerslijst/detail/bewerken en invite/reset-acties. `player/src/routes/admin/+layout.ts` laadt nog één mockdatabase voor de adminrouteboom. De overige routes gebruiken daardoor indirect mockdata en mogen niet als echte backenddata worden gepresenteerd.

## Werkelijke backendgrens

`CampaignService`, `PollService`, `PaymentService` en `DashboardService` bestaan en worden gerouteerd, maar hun dekking is niet gelijk aan de documentatie. Er ontbreken bijvoorbeeld actuele routes voor memberships, poll-detail/options/participants en payment-obligationbeheer. De bestaande routes gebruiken soms `basename()` voor IDs; nieuwe aansluitingen moeten segmenten expliciet parsen zoals de herstelde disable-route.
