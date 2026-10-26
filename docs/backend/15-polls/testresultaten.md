# Testresultaten

## Laatste uitvoering

- `backend/scripts/run-integrations.ps1`: migraties vanaf nul en tweede ronde met uitsluitend `SKIP`: geslaagd.
- Backendpollvalidaties: geslaagd voor single/multiple, labelwijziging, optie van andere poll, verlopen poll, niet-deelnemer en onbevoegde beheerder. De multiple-choice sleutelwijziging staat in migratie 006.
- Bestaande auth-, campaign-, membership-, sessie- en beschikbaarheidsregressies: geslaagd binnen dezelfde runner tot aan de browserfase.
- Browserpollflows: niet geslaagd. De echte backend wordt gebruikt, maar de fixture-admin is niet als ingelogde UI-gebruiker beschikbaar; de test stopt vóór pollcreatie. Dit is geen geslaagde browservalidatie.
- De gecombineerde browserrun bevat daarnaast een bestaande auth-reset-regressie waarbij de runner-admin na reset niet opnieuw zichtbaar ingelogd raakt. Dit is afzonderlijk van de backendpollvalidatie.

## Statisch uitgevoerd

- `php -l backend/src/Domain/PollService.php`: geslaagd.
- `php -l backend/public/index.php`: geslaagd.

De frontendchecks en integratietests moeten worden uitgevoerd vanuit de bestaande lokale omgeving met de testdatabase. Een build of syntaxcheck alleen is geen functionele pollvalidatie.

## Te bewijzen met de geïsoleerde backend

1. Aanmaak van single- en multiple-poll en detail na herladen.
2. Niet-deelnemer krijgt 403; deelnemer kan alleen eigen toegestane poll stemmen.
3. Verkeerde option-ID of meerdere opties bij single geeft 422.
4. Sluiten tijdens een overlappende stemactie geeft één deterministische uitkomst door de polllock.
5. Dubbele inzendingen blijven één stemset per gebruiker.
6. Stale `version` geeft 409; gewijzigde labels behouden option-ID en resultaten.
7. Resultaten zijn alleen voor campagneleden; resource-ID’s worden opnieuw server-side gecontroleerd.

Er wordt geen InfinityFree-resultaat geclaimd.
