# Bevindingen

De algemene player/adminshell gebruikt nog mockdata. Alleen de gebruikerslijst en user-detailroute zijn voor deze herstelstap naar de centrale API-grens gebracht. Campagnes, kalender en overige domeinschermen blijven expliciete demo/mockschermen.

Registratie gebruikt echte invite-validatie en registratie-endpoints. Login/logout gebruikt nu de centrale API-client; reset valideert email/username vóór het atomair instellen van het nieuwe wachtwoord. Dit is statisch gecontroleerd maar niet runtime getest.

Migraties zijn inhoudelijk behouden en uitsluitend geconsolideerd onder `backend/database/migrations/`. De runner leest alleen die map. `.env` wordt nu daadwerkelijk door PHP ingelezen.

De database is lokaal beschikbaar op `127.0.0.1:3307`; `backend/.env` is aangemaakt en wordt door PHP geladen. De browserloginfout was een hydration-race: de test klikte vóór `networkidle`. Na wachten op de gehydrateerde app en het ongeldig maken van de CSRF-cache na sessierotatie slagen login/logout.

Invite- en resetuitgifte/intrekking zijn nu als echte adminacties aangesloten met de centrale API-client en gedeelde CopyButton. De volledige browserflow is lokaal functioneel getest met een verse run-database. De uitgebreide concurrency-/resetmatrix is nog niet volledig geautomatiseerd.
