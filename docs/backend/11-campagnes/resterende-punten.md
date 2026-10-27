# Resterende punten

1. Onderzoek en herstel de restore-fout uit `campaign_flow.php`; create/list/detail/archive zijn bewezen, restore nog niet.
2. Test `409 conflict` met twee versies en controleer dat de oude data niet wordt overschreven.
3. Test portaladmin, campaign-DM, speler en handmatig gewijzigde campaign-ID’s tegen de echte endpoints.
4. Test one-shots met `type=one_shot` in dezelfde scenario’s.
5. Sluit daarna memberships aan; campagne-DM-bevoegdheden zijn nu server-side aanwezig maar de memberships-UI blijft mockdata.
6. Voer browsertests uit tegen de echte backend vóór deze functie productiegeschikt wordt genoemd.
7. InfinityFree blijft afzonderlijk openstaand: webroot, PHP-extensies, HTTPS/cookies, database en productie-deployment.
