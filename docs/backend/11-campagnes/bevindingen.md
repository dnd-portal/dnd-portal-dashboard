# Bevindingen

| Onderdeel | Frontendroute | Databron | Endpoint/rechten | Status | Testdekking | Resterend |
|---|---|---|---|---|---|---|
| Campagnewijzer | `/admin/campaigns` | API via centrale client | `GET /api/v1/campaigns`; lid/admin lezen | Aangesloten op `DataTable` | Check/build; runtime niet uitgevoerd | echte DB-flow uitvoeren |
| Campagne maken | `/admin/campaigns` | API | `POST /api/v1/campaigns`; portaladmin | Aangesloten | Statisch gecontroleerd | API-integratietest |
| Campagne detail/bewerken | `/admin/campaigns/[id]` | API | `GET/PATCH /api/v1/campaigns/{id}`; lid lezen, DM/admin beheren | Aangesloten | Check/build; runtime niet uitgevoerd | versieconflict en autorisatie testen |
| Archiveren/herstellen | campagne-detail | API | PATCH `archived_at`; DM/admin | Aangesloten, historie blijft bestaan | Niet functioneel getest | archive/restore runtimecheck |
| One-shotlijst/detail | `/admin/one-shots`, `/admin/one-shots/[id]` | Dezelfde API, filter `type=one_shot` | Zelfde endpoints/rechten | Aangesloten | Check/build; runtime niet uitgevoerd | echte one-shot create/edit/archive testen |

De frontend gebruikt geen `getDatabase()` meer in deze vier gemigreerde routes. De bestaande adminlayout kan nog mockdata laden voor andere adminroutes; dat is buiten deze campagneflow en wordt niet als campagnebron gebruikt.
