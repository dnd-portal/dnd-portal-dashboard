# Opleveringscontrole

## Eindstatus

De lokale frontend is gecontroleerd en de PHP-bestanden zijn syntactisch geldig. De MariaDB 11.4-testcontainer draait lokaal gezond, maar de backend is **niet productiegeschikt verklaard**: InfinityFree-routing, webroot, HTTPS, cookies en extensies zijn niet op hosting gecontroleerd.

Dashboard, navigatie, auth, campagne-, poll- en betaalpagina’s die in de recente verticale stappen zijn gemigreerd gebruiken de centrale API en tonen API-fouten. Niet-gemigreerde adminpagina’s hebben nog een demo-context; die is een releaseblokkade voor een volledige productieclaim.

Leesvolgorde: [verificatie.md](verificatie.md), [deployment.md](deployment.md), [backup-herstel.md](backup-herstel.md), [resterende-punten.md](resterende-punten.md).

