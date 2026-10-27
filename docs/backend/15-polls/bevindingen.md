# Bevindingen

De database bevat polls, poll_options, poll_participants en poll_votes. `poll_votes` gebruikt samengestelde foreign keys zodat een optie bij dezelfde poll hoort en stemmen aan een deelnemer gekoppeld zijn. Er is geen aparte opslag voor “gereageerd zonder optie”; dat blijft daarom onderscheiden van niet reageren via het bestaande model alleen zolang een expliciete lege stem niet wordt toegestaan.

Voorheen waren de pollpagina’s uitsluitend `EntityTable`/mockdata. De backend had alleen campagne-lijst, aanmaak en vote. Detail, statuswijziging, resultaten en participantcontrole ontbraken.
