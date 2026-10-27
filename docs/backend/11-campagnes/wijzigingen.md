# Wijzigingen

- `CampaignService` ondersteunt portaladmin-lijsten naast actieve memberships en een geautoriseerd detailendpoint.
- Campaign-updates gebruiken `version` als optimistic-lockingvoorwaarde en geven `409 conflict` bij een verouderde versie.
- Bestaande historische rijen blijven behouden; archiveren zet alleen `archived_at`, herstellen maakt die waarde NULL.
- Campagne- en one-shotroutes gebruiken `ApiCampaignTable`, `ApiCampaignDetail`, de centrale `api`-client en bestaande `DataTable`/admincomponenten.
- `campaigns.type` bepaalt het overzicht; er is geen aparte one-shotimplementatie.

Niet gewijzigd: sessies, membershipschermen, mockdata voor nog niet gemigreerde domeinen en bestaande gebruikersdata.
