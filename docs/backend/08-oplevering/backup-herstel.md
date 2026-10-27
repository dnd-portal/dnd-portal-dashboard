# Backup en herstel

## Export

Gebruik een private operatorverbinding of phpMyAdmin-export met schema, data, foreign keys en charset/collation. Bewaar het dumpbestand versleuteld buiten de publieke hostingmap, buiten git en buiten frontendbundels. Noteer datum, databaseversie en migratiechecksums apart.

## Herstel

1. Stop writes of zet de applicatie tijdelijk in maintenance buiten de publieke API.
2. Maak eerst een export van de huidige toestand.
3. Herstel de dump in een geïsoleerde database of via gecontroleerde phpMyAdmin-import; gebruik geen publiek PHP-script.
4. Controleer row counts, foreign keys, `schema_migrations`, login/sessie, een pollstem, een payment SUM en dashboardprivacy.
5. Wijs de applicatie pas om nadat de herstelcontrole slaagt.

## Hersteltest en verlies

Een hersteltest is pas geslaagd wanneer de dump in een afzonderlijke database is geïmporteerd en bovenstaande functionele queries dezelfde verwachte resultaten geven. Data die na het dumpmoment is aangemaakt kan verloren gaan. Ook niet in de dump opgenomen bestanden, tijdelijke sessiecookies, externe betaalproviderstatus (die niet bestaat in deze scope) en niet-gecommitteerde migraties kunnen verloren gaan.

