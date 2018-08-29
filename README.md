# Symcon_SMS

## Dieses Modul bietet erweiterte SMS-Funktionlitäten für IP-Symcon um ein Hardware SMS-Gateway. 

### Vorraussetzungen: 
 - IP-Symcon V.4.0 ([LINK](https://www.symcon.de))
 - Teltonika-Router RUT955, RUT 950, RUT240  ([LINK](https://teltonika.lt/de/product/rut950/))
** Erstellt und getestet wurde dieses Modul mit dem Teltonika RUT955. Dieses Modul sollte jedoch auch  mit den Routern RUT950 und RUT240 funktionieren (Erfahrungsberichte nehme ich gerne entgegen.


### Modul URL:

[https://github.com/timo-u/Symcon_SMS](https://github.com/timo-u/Symcon_SMS)

### Teltonika SMS Gateway  

Zur Umsetzung der SMS wird ein Teltonika Router verwendet. 
Das Gateway wird als I/O- Instanz in IP-Symcon eingebunden. 

[Beschreibung](https://github.com/timo-u/Symcon_SMS/blob/master/TeltonikaGateway/README.md)

### SMSDevice

Das SMS-Device Stellt ein beliebiges Gerät mit SMS-Kommuniaktion dar. Eine vom Gateway empfangene Nachricht wird bei richtiger Telefonnummer auf das SMSDevice weiter geleitet. Im SMSDevice kann ein Script Hinterlegt werden was beim Empfang einer SMS ausgeführt wird. 

[Beschreibung](https://github.com/timo-u/Symcon_SMS/blob/master/SMSDevice/README.md)



## Haftungsausschuss
Dieses Modul wurde sehr gewissenhaft entwicket. Trotz der Sorgfalt können sch Fehler einschleichen. 
Ich übernehme keine Haftung für Fehlfunktionen und daraus resultierenden Kosten.
