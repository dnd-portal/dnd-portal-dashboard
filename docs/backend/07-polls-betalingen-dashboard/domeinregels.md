# Domeinregels en rechten

Pollopties zijn ID-gebaseerd. Niet reageren is anders dan reageren met een ongeldige optie; een ongeldige optie wordt geweigerd en niet opgeslagen. Alleen poll-deelnemers mogen stemmen. `single` accepteert exact één option-ID; `multiple` accepteert één of meer unieke IDs.

Portaladmin beheert alles; campaign-DM alleen eigen campagnes; spelers lezen alleen eigen campagnecontext en zien alleen eigen financiële obligations. Alleen bevoegde beheerders registreren of corrigeren ontvangen betalingen. Geen provider, webhook of automatische bankcontrole.

Alle lijstqueries moeten filter/sortering allowlisten en vóór paginering op de volledige dataset toepassen. Geen directe databasekennis in Svelte-componenten.

