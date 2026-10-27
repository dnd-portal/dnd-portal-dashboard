# V4 handmatige acceptatie

Gebruik een normale browser voor styling en navigatie. Gebruik uitsluitend de testidentifiers uit `backend/.env` of `backend/.env.preview`; wachtwoorden worden niet geprint.

## Development

```powershell
portal run-development-server
```

Controleer de homepage, publieke navigatie, authlayout en directe routes. Log in als lokale admin en controleer:

1. Maak een globale poll met meerdere datumopties.
2. Maak een Type A-invite voor de pending gebruiker.
3. Maak een Type B-invite.
4. Registreer Type A in een privévenster.
5. Beantwoord de poll, refresh en wijzig het antwoord.
6. Log uit, log opnieuw in en controleer het antwoord.
7. Registreer Type B en beantwoord dezelfde globale poll.
8. Bekijk als admin de resultaten.
9. Sluit de poll en controleer dat een gebruiker niet meer kan wijzigen.

Controleer desktop en mobiele breedte op homepage, login, register, dashboard, poll en adminpagina's. Controleer focus, foutmeldingen, loading/successstates en dat er geen horizontale overflow is.

## Deployment-preview

```powershell
portal run-deployment-server
```

Herhaal de kernflow tegen `http://127.0.0.1:8184`. Dit is de werkelijk uitgepakte deployment-ZIP via Apache/PHP, zonder Vite.

## Afronden

```powershell
portal status
portal stop
```

Een normale stop behoudt databases en volumes. Gebruik geen resetactie tijdens deze controle.
