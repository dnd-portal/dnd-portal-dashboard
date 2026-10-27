# Bevindingen

| Functie | Route | Bron | Backend | Status |
|---|---|---|---|---|
| Sessielijst/detail | `/admin/sessions`, `/admin/sessions/[id]` | Mock | sessions per campaign, PATCH session | Niet frontend aangesloten |
| Sessie maken/bewerken | bestaande adminroutes | Mock | POST/PATCH sessions, DM/admin | Backend aanwezig |
| Deelnemers | `/admin/attendance` en sessiedetail | Mock | nieuw `GET /api/v1/sessions/{id}/participants`, PATCH participant | Read-endpoint toegevoegd; UI ontbreekt |
| Eigen RSVP | bestaande sessiecontext | Mock | participant PATCH, speler alleen eigen RSVP | Niet frontend aangesloten |
| Aanwezigheid | attendancecomponent | Mock | manager-only attendance field | Niet frontend aangesloten |
| Annuleren | sessie-editor | Mock | status `cancelled`, historie blijft | Niet functioneel getest |

`rsvp_status` en `attendance_status` blijven afzonderlijke kolommen. De database bewaart deelnemershistorie met `ON DELETE RESTRICT`. Tijdvelden worden als UTC opgeslagen; lokale DST-validatie is nog niet geïmplementeerd in de frontend.
