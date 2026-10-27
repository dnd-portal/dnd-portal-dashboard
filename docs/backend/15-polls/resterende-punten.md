# Resterende punten

- De lokale browserflow en MariaDB-integratietest voor polls moeten nog daadwerkelijk worden gedraaid en aan dit verslag worden toegevoegd.
- De adminlijst vereist nu handmatige campagne-ID-invoer; een campagnekeuzelijst en participantbeheer-UI zijn nog niet volledig aangesloten.
- Optielabel wijzigen en opties toevoegen hebben nog geen aparte publieke route/UI. Opties verwijderen blijft bewust niet ondersteund na invoering van stemmen.
- Het bestaande model kent geen expliciete `responded_without_option`-waarde; die semantiek is dus niet beschikbaar zonder schemawijziging.
- InfinityFree moet apart worden gecontroleerd op PHP-versie, MariaDB-constraints, locking, cookies en HTTPS.
