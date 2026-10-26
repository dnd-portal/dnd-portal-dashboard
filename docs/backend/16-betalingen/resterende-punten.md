# Resterende punten

- Er ontbreekt nog een expliciet ontwerp voor negatieve correcties/verrekeningen. De huidige schema’s hebben alleen unsigned bedragen en `correction_of_entry_id`; er mag geen ongedocumenteerde saldo-inversie worden ingevoerd.
- Er is nog geen volledige spelerweergave voor `GET /api/v1/me/payment-obligations` in een afzonderlijk bestaand scherm aangesloten.
- Verplichtingen bewerken, kwijtschelden en verzoeken sluiten zijn niet als nieuwe features toegevoegd omdat de bestaande API- en UI-acties daarvoor geen compleet contract bevatten.
- Integratie- en browsertests moeten nog op een verse MariaDB-testdatabase worden uitgevoerd.
- InfinityFree-validatie van URL-beleid, locking, transacties en productiecookie/HTTPS-configuratie blijft open.
