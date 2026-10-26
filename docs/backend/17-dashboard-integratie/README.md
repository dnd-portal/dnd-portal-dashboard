# Dashboard-integratie

Het dashboard gebruikt nu de centrale `/api/v1/dashboard`-response voor de volgende sessie, toegestane deelnemers/RSVP-samenvatting, open polls en eigen openstaande bijdragen. Portaladmins krijgen daarnaast server-berekende beheercounts en beperkte auditactiviteit.

Status: backendruntime gecontroleerd met echte MariaDB-fixtures; browserdashboardvalidatie blijft beperkt doordat de gecombineerde runner faalde in een bestaande pollfixture. InfinityFree is niet getest.
