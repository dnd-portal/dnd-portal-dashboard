# Betalingen

Deze stap sluit betaalverzoeken, verplichtingen en geregistreerde ontvangsten aan op de bestaande PHP/MySQL-API. Bedragen blijven gehele centen met een ISO-valuta. De speler kan alleen eigen verplichtingen lezen; alleen portaladmins en bevoegde campagne-DM’s registreren ontvangsten.

Status: lokaal functioneel getest met echte PHP/MariaDB en browserflow. InfinityFree is niet getest.

De runner valideert bedragen in centen, deel- en volledige betalingen, reversal-historie, valuta- en verplichtingsrelaties, eigen spelerdata en server-side schrijfrechten. Payment-subroutes gebruiken expliciete resource-ID parsing; `basename()` wordt niet gebruikt voor actieroutes.
