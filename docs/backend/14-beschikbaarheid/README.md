# Beschikbaarheid

De bestaande `AttendanceCalendar` gebruikt nu de centrale API-client en geen mockdatabase meer. Kalenderdagen blijven DATE-strings; de PHP-API accepteert alleen `YYYY-MM-DD`, begrenst de periode en ondersteunt verwijderen naar onbekend.

Status: backend- en frontendkoppeling zijn geïmplementeerd. De echte runner test opslaan, herladen, DATE-behoud en verwijderen naar onbekend. Browserinteractie gebruikt de centrale API-client; InfinityFree-validatie blijft open.
