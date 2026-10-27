# Implementatie

`CampaignService` gebruikt prepared PDO-queries en expliciete responsevelden. Campagnes worden gefilterd op actieve membership; een speler krijgt geen campagne waar hij vertrokken is. Admins mogen alle relevante records beheren; een DM alleen de eigen actieve campagne. Elke URL-ID wordt opnieuw server-side gecontroleerd.

Sessies hebben aparte `rsvp_status` en `attendance_status`. Spelers mogen alleen hun eigen RSVP wijzigen en alleen vóór `rsvp_deadline_at`; attendance is manager-only. `user_availability` heeft één rij per gebruiker/kalenderdag; geen rij betekent onbekend. Instanties worden als UTC opgeslagen, kalenderdagen als DATE; `timezone` staat op de campagne.

De nieuwe additive migratie voegt `campaigns.archived_at` en `game_sessions.rsvp_deadline_at` toe. Annuleren gebruikt de bestaande session status; archiveren vult `archived_at`. Geen deletes of automatische seeds.

