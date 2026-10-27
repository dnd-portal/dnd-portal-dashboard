# Implementatie

`PollService` maakt polls met `single`/`multiple`, opties met stabiele IDs en stemt alleen voor actieve campagneleden. Gesloten of verlopen polls weigeren stemmen. De samengestelde foreign key beschermt dat poll en optie bij elkaar horen. Opties worden na stemmen niet verwijderd of hergebruikt; labelwijziging verandert option-ID en historische stemmen niet.

`PaymentService` gebruikt cents, valuta en externe URL-validatie. Alleen admin/DM maakt requests of entries; spelers krijgen alleen eigen obligations. Openstaand wordt `amount_cents - SUM(confirmed entries)` berekend. Deelbetalingen zijn toegestaan; correcties verwijzen naar een eerdere entry en verwijderen nooit financiële historie.

`DashboardService` berekent de eerstvolgende zichtbare geplande sessie, open polls zonder eigen vote en het aantal eigen openstaande obligations. Andere gebruikers/auditgegevens worden niet teruggegeven.

