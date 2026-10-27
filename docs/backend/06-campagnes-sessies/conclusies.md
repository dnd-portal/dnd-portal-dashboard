# Conclusies

De relationele backendlaag voor campagnes, one-shots, sessies, deelnemers en beschikbaarheid is aanwezig met server-side ownershipchecks en historische behoudregels. De openstaande beperking is operationeel: er is geen lokale MySQL om transacties, foreign keys, DST en gelijktijdige updates daadwerkelijk te testen. De frontendpagina’s moeten per functie worden omgezet nadat deze API op een geïsoleerde database is gevalideerd.

