# Wijzigingen

In deze inventarisatiefase zijn geen domeinfeatures of mockdatabronnen omgezet.

Wel zijn documentatieverschillen vastgesteld:

- De eerdere API-documentatie beschreef membership-, poll-detail- en payment-detailroutes die niet in `backend/public/index.php` staan. Die worden hier als ontbrekend gemarkeerd, niet als bestaand beschouwd.
- De eerdere status “services aanwezig” is gesplitst in backend aanwezig versus frontend aangesloten. Een PHP-service is geen bewijs van een werkende gebruikersflow.
- Dashboardtotalen zijn alleen serverberekend in `DashboardService`; de frontend gebruikt nog de admin/mockcontext.
- `campaigns.type` blijft de enige bron voor campaign versus one-shot; er is geen aparte one-shotbackend.

Volgende implementaties moeten hergebruiken: `player/src/lib/api/client.ts`, `DataTable`, `DropdownSelect`, `AttendanceCalendar`, bestaande `AdminHeader`/`AdminDetail`-compositie, externe SCSS en respectievelijk `CampaignService`, `PollService`, `PaymentService` en `DashboardService`. Er is geen tweede API-client, tabelarchitectuur of domeinservice nodig.
