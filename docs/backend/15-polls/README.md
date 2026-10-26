# Polls

Deze stap sluit de bestaande pollroutes aan op de PHP/MySQL-backend. De adminlijst vraagt expliciet een campagne-ID; de detailpagina leest en wijzigt alleen de echte API-resource.

Status: backendruntime is lokaal getest met echte MariaDB. Pollroute-ID-parsing, aanmaken, deelnemers, single/multiple votes, resultaten, labelwijzigingen en sluiten zijn regressiegedekt. Multiple-choice gebruikt migratie 006 met één sleutelrij per poll/gebruiker/optie. De gedeelde poll-detailpagina is beschikbaar voor admin en speler; de echte speler-browserflow wordt door de integratierunner uitgevoerd. InfinityFree blijft afzonderlijk open.

Leesvolgorde: bevindingen.md, wijzigingen.md, testresultaten.md, resterende-punten.md.
