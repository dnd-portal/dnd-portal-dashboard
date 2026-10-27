# Bevindingen

- De kalender gebruikte eerder `getDatabase()` en mockrecords met `unavailableUserIds`.
- `user_availability` heeft een samengestelde primaire sleutel `(user_id, availability_date)` en voorkomt dubbele records.
- `GET /api/v1/availability` retourneert voor admins toegestane planningsrecords met gebruiker en datum; gewone gebruikers krijgen alleen eigen records.
- `PUT /api/v1/availability/{date}` schrijft uitsluitend de huidige gebruiker.
- `DELETE /api/v1/availability/{date}` verwijdert de eigen invoer; daarna betekent ontbreken onbekend.
- De API gebruikt geen lokale tijdzoneconversie voor DATE-waarden. De bestaande kalender toont maand/dagvelden lokaal zonder datum naar UTC te verschuiven.
