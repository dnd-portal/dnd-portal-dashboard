# Resterende punten

1. Sluit campagnes als eerste domein aan via de centrale API-client: lijst, detail, create/edit, type, archiveren en memberships.
2. Voeg ontbrekende concrete membershiproutes toe of documenteer bewust dat membershipbeheer nog operator-only is.
3. Sluit sessies aan inclusief RSVP, deadline, aanwezigheid, annuleren en versieconflicten. Behoud historische deelnemers.
4. Sluit beschikbaarheid aan met expliciete behandeling van ontbrekende rijen als onbekend en correcte datum/tijdzoneweergave.
5. Voeg pollroutes toe voor detail, opties en deelnemers voordat de UI van mock naar API gaat; test optie-ID-integriteit en gesloten polls.
6. Werk betalingsobligations en correcties uit als API-contract; bereken saldo server-side en test autorisatie/financiële historie.
7. Sluit dashboard en navigatietotalen aan zonder `getDatabase()`-fallback voor echte gebruikers. Niet-gemigreerde schermen moeten zichtbaar als demo worden gemarkeerd.
8. Voeg per domein geïsoleerde backend- en browserintegratietests toe vóór de mockgrens wordt verwijderd.
9. Voer InfinityFree-controles afzonderlijk uit: PHP/extensies, webrootafscherming, HTTPS/cookies, MySQL-runtime en migratieprocedure.

Geen van deze punten is in deze inventarisatie uitgevoerd; er zijn geen seeds, productiegegevens of bestaande databases gewijzigd.
