### GX107

Das GX107 ist ein GSM-Schaltmodul welches bei Conrad Elektronik erhältlich ist. Die Instanz bildet die zwei Ausgangskanäle und den Eingangskanal ab und Zeigt Betriebsspannung und Verbindungsstärke an. 
Die Instanz funktioniert sowohl für die Geräte GX107 als auch GX107-4G.  


#### Einstellungen der Geräte-Instanz

##### Telefonnummer
Telefonnummer des Gerätes. (z.B. "+49 171 123456" )
Die Telefonnummer muss hierfür mit "+" und Ländervorwahl angegeben werden, da sonst der Vergleich der Nummer scheitert.
Die Leerzeichen und Bindestriche in der Telefonnummer werden herausgefiltert.

##### Pin
Pin-Nummer des GSM-Moduls. Die Pin-Nummer wird in jerder Nachricht zur Validierung versendet.

##### Kanal 1 automatisch einschalten
Wenn dieser Schalter aktiviert ist wird der Ausgang automatisch eingeschaltet wenn eine Status-Nachricht mit ausgeschaltetem Ausgang empfangen wird. 


##### Kanal 2 automatisch einschalten
Wenn dieser Schalter aktiviert ist wird der Ausgang automatisch eingeschaltet wenn eine Status-Nachricht mit ausgeschaltetem Ausgang empfangen wird. 

##### Übergeorndete Instanz
Als übergeordnete Instanz wird das Gateway ausgewählt 
 

#### Setzen der Ausgänge
```php
SMS_GX107SetOutput(11111, 1 /* Kanal 1 */ , true /* einschalten */);

```

#### Neustarten eines Ausgangs
Der ausgewählte Ausgang wird abgeschaltet und nach der im letzten Parameter eingegebenn Wartezeit (in Sekunden) wieder eingeschaltet.  
```php
SMS_GX107RestartOutput(11111, 1 /* Kanal 1 */, 30 /*sekunden Wartezeit*/);

``` 
#### Abfragen des Status
```php
SMS_GX107GetStatus(11111);

``` 

#### Logging für Statusvariablen aktivieren 
```php
IPS_RequestAction(11111, "EnableLogging", "");
``` 

#### Logging für Statusvariablen deaktivieren 
```php
IPS_RequestAction(11111, "DisableLogging", "");
``` 
