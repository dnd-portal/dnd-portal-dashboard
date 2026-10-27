# Wijzigingen

- `CampaignService::members()` toegevoegd met beperkte responsevelden.
- Membershiproutes toegevoegd voor lezen, toevoegen/reactiveren, rolwijziging en vertrek.
- Server-side `assertManage` blijft de actor- en campagnegrens bepalen; requestvelden kunnen geen portalrol geven.
- `/admin/memberships` gebruikt nu de centrale API-client en `DataTable` in plaats van `EntityTable`/mockdatabase.
- Geen nieuwe stylinglaag, tabelarchitectuur of database-entiteit toegevoegd.

De bestaande `ON DUPLICATE KEY UPDATE`-upsert voorkomt dubbele actieve koppelingen. De service laat campagne-DM’s binnen hun eigen campagne werken; portaladmin blijft globaal.
