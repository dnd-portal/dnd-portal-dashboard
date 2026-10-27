# Implementatie en rechten

`UserEditor.svelte` maakt een draft bij Edit, bewaart pas bij Save en gebruikt `version` voor optimistic locking. De server accepteert uitsluitend `display_name`, `email`, `username` en `roles`; wachtwoorden, hashes, ID’s en registratiehistorie kunnen niet worden gepatcht.

Alle usermutaties vereisen een actieve `portal_admin`, CSRF en toegestane Origin. Email/username worden genormaliseerd en database-uniciteit blijft leidend. Een admin kan zichzelf niet uitschakelen en het laatste actieve portaladmin-account kan zijn rol niet verliezen. De server is autoriteit; verborgen UI-knoppen zijn geen beveiliging.

Disable is een aparte expliciete actie en revoket bestaande sessies. Rechtenwijzigingen worden bij elke request opnieuw gelezen.

