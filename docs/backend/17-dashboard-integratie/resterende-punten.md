# Resterende punten

- De adminlayout laadt voor niet-gemigreerde schermen nog de demo-databasecontext omdat die componenten nog mockgebonden zijn. Dit is bewust niet stilzwijgend omgezet; die schermen moeten per verticale flow worden aangesloten.
- De dashboardactiviteit toont eventtype en tijd, maar geen actornaam; dat voorkomt onnodige persoonsgegevens en vraagt een expliciet productbesluit voor rijkere activiteit.
- De nieuwe dashboardqueries en navigatie moeten nog met de bestaande geïsoleerde MariaDB-runner en echte browserflow worden getest.
- InfinityFree-validatie van querygedrag, performance, PHP-configuratie, cookies en HTTPS blijft open.
