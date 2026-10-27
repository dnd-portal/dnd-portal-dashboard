# Wijzigingen

- `CampaignService::participants()` toegevoegd met beperkte responsevelden.
- `GET /api/v1/sessions/{id}/participants` toegevoegd.
- De bestaande participant-mutatie blijft actor, campagne, doelgebruiker en managerstatus server-side controleren.
- Geen mockdata, styling of bestaande datumcomponenten verwijderd.

De sessielijsten en forms zijn in deze stap nog niet naar de API omgezet; dat is bewust als resterend werk vastgelegd in plaats van een half werkende fallback te introduceren.
