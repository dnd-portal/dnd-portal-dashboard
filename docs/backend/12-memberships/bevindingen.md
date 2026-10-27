# Bevindingen

| Functie | Frontend | API/rechten | Status | Testdekking | Resterend |
|---|---|---|---|---|---|
| Ledenoverzicht | `/admin/memberships` | `GET /api/v1/campaigns/{id}/members`; lid ziet zichzelf, DM/admin de campagneleden | Aangesloten | Statisch | Runtime-autorisatie testen |
| Gebruiker toevoegen | bestaand membershipformulier | `POST .../members`; actor moet DM/admin zijn; target is bestaand user-ID | Aangesloten | Statisch | echte DB-flow |
| Functie wijzigen | bestaand formulier | `PATCH .../members/{userId}` met alleen `member|dm` | Aangesloten | Statisch | promotie/demotie testen |
| Lid beëindigen | bestaand formulier | `POST .../members/{userId}/leave`; DM/admin | Aangesloten | Statisch | historie en dubbele requests testen |
| Heractivatie | knop Add or reactivate | bestaande koppeling krijgt `left_at=NULL` via upsert | Aangesloten | Statisch | echte heractivatie testen |

De database gebruikt één primaire koppeling per campaign/user. Vertrek zet `left_at`; historische sessiedeelnemers, stemmen en financiële relaties worden niet verwijderd.
