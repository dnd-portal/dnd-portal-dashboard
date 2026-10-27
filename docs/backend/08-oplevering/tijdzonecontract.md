# Tijdzonecontract

## Huidige contract

- Opslag: `game_sessions.session_date` is een kalenderdatum zonder offset. `starts_at` en `ends_at` zijn `DATETIME(6)` en bevatten geen timezone-offset; de backendverbinding hoort UTC te gebruiken.
- API: campagne en gebruiker hebben een IANA-naam in `timezone` (standaard `UTC`). Sessieresponses leveren de opgeslagen datum/tijd terug; ze bevatten nog geen expliciete `timezone`- of offsetvelden.
- Weergave: de frontend mag een sessietijd daarom alleen tonen als opgeslagen campagnetijd volgens een nog te besluiten productcontract. Een browserlokale omzetting is nu niet aantoonbaar correct.

## Productbesluit nodig

Aanbeveling: bewaar sessiemomenten als UTC instant (`starts_at_utc`, `ends_at_utc`) en bewaar de IANA-campagnetijdzone als presentatiemetadata. Valideer lokale invoer met die zone en weiger ambigue/niet-bestaande tijden tenzij de gebruiker expliciet een keuze maakt. Tot die wijziging is DST niet getest en mag de UI geen lokale omzetting claimen.

Schrikkeldagen zijn kalenderdata en vallen onder dezelfde ±366-dagenregel; verruim die productregel niet voor deze controle.
