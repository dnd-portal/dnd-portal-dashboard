# Implementatie

`backend/src/Auth/` bevat de authlaag. `SessionStore` gebruikt uitsluitend `auth_sessions`: de cookie bevat een opaque random waarde, de database bevat alleen SHA-256 daarvan, met idle- en absolute vervaltijd. Login revoket eerdere sessies en maakt een nieuwe sessie. Iedere request controleert accountstatus opnieuw, zodat disabled accounts en gewijzigde rechten direct doorwerken.

`AuthService` implementeert login op genormaliseerde email of username, `password_verify`, `password_needs_rehash`, `/me`, logout, invite-activatie en password reset. Invite/reset-uitgifte is admin-only en retourneert de ruwe code uitsluitend in het directe 201-resultaat. Resetcodes zijn 30 minuten geldig; nieuwe uitgifte maakt eerdere actieve codes voor hetzelfde doel ongeldig.

`Security` zet HttpOnly-, SameSite=Lax- en in productie Secure-cookies. Mutaties vereisen een exacte geconfigureerde Origin en `X-CSRF-Token`. De frontendclient blijft de enige fetchlaag en bewaart geen authgeheimen in localStorage.

## Nog niet in deze stap

De UI is nog niet aan de echte auth-endpoints gekoppeld; de bestaande authschermen blijven mock/demo. Dat voorkomt een half gemigreerde flow. De volgende stap maakt de login-, registratie- en resetpagina’s API-aware met dezelfde bestaande SCSS.

