# Beveiligingsbesluiten

- Alleen `account_status=active` kan inloggen.
- Identifiers worden getrimd en casefolded; dubbele email/username-matches worden niet willekeurig gekozen.
- Wachtwoorden worden niet getrunceerd; de backend valideert lengte vóór `password_hash`. Hashes passen in VARCHAR(255).
- De codegenerator gebruikt 32 random bytes per code (256 bits; boven de minimumgrens van 128 bits).
- Invite/resetcodes zijn eenmalig, verlopen, herroepbaar en niet leesbaar na opslag. Ze komen niet in logs of algemene responses.
- Reset wijzigt het wachtwoord en consumeert de code in één database-transactie; daarna worden alle sessies van de gebruiker ingetrokken.
- Reset maakt het oude wachtwoord niet ongeldig bij uitgifte en logt de gebruiker niet automatisch in.
- Rate limiting gebruikt gehashte keys in `rate_limit_buckets`; login wordt per bron begrensd. De limieten moeten op doelhosting worden afgestemd.
- Rechtewijzigingen worden server-side uit de database gelezen. Portaladmin is niet hetzelfde als campaign-DM.
- Er is geen maandelijkse verplichte wachtwoordwijziging.

## Belangrijke beperking

De gekozen sessiestrategie vereist dat `auth_sessions` bestaat en de databaseverbinding beschikbaar is. Zonder database is er bewust geen onveilige fallback naar PHP-filesessies, JWT of localStorage.

