# Wijzigingen

- `DashboardService` berekent volgende toegestane sessie, deelnemers, geaccepteerde RSVP’s, open polls en eigen openstaand bedrag server-side.
- Portaladmins krijgen extra totals voor gebruikers, actieve campagnes, geplande sessies, open polls, open verplichtingen en de laatste vijf auditgebeurtenissen.
- `AdminOverview` gebruikt uitsluitend de centrale API-client en toont loading-, fout- en lege statussen.
- Navigatiecounts worden vanuit dezelfde dashboardresponse geladen; mockarraylengtes worden daar niet meer voor gebruikt.
- Responses bevatten geen credentials, financiële entries van anderen of interne databasevelden.
