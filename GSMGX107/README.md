### SMSDevice

Das SMS-Device Stellt ein beliebiges Gerät mit SMS-Kommuniaktion dar. Eine vom Gateway empfangene Nachricht wird bei richtiger Telefonnummer auf das SMSDevice weiter geleitet. 
Im SMSDevice kann ein Script Hinterlegt werden was beim Empfang einer SMS ausgeführt wird. 


#### Einstellungen der Geräte-Instanz

##### Telefonnummer
Telefonnummer des Gerätes. (z.B. "+49 171 123456" )
Die Telefonnummer muss hierfür mit "+" und Ländervorwahl angegeben werden, da sonst der Vergleich der Nummer scheitert.
Die Leerzeichen und Bindestriche in der Telefonnummer werden herausgefiltert.

##### Pin
Pin-Nummer des GSM-Moduls. Die Pin-Nummer wird in jerder Nachricht zur Validierung versendet.

##### Aktualisierungs-Intervall
Das Aktualisierungs-Intervall gilt für die Abfrage des GSM-Moduls. In dem Dort eingestellten Zyklus wird der Status des GSM-Moduls abgerufen. 
Wenn hier "deaktiviert" eingestellt ist werden keine selbstständigen Nachrichten an das Modul gesendet. 


##### Verbindungs-Warnung
Wird innerhalb der hier eingestellten Zeit keine SMS des GSM-Moduls empfangen wird die Variable Verbindungsfehler gesetzt.


##### Übergeorndete Instanz
Als übergeordnete Instanz wird das Gateway ausgewählt 


 

#### Setzen der Ausgänge
```php
SMS_GX107SetOutput(11111 /*[Test\SMSDevice]*/, 1 /* Kanal 1 */ , true /* einschalten */);

```

#### Abfragen des Status
```php
SMS_GX107GetStatus(11111 /*[Test\SMSDevice]*/);

``` 

