# Speelsessies

Deze fase inventariseert en breidt de bestaande sessie-API uit met een expliciete deelnemersread-endpoint. De bestaande sessie- en participantmutaties blijven server-side gecontroleerd.

Status: backendroutes en centrale API-koppelingen zijn functioneel getest. De sessie- en detailpagina gebruiken de centrale API-client; RSVP en attendance blijven afzonderlijke participantvelden. Route-ID-parsing, rechten en annulering zijn regressietestgedekt. InfinityFree-validatie blijft open.
