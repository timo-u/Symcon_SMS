### iSocket
Das iSocket ist ein GSM-Schaltmodul für die Steckdose. Die Instanz den Ausgangskanal ab und zeigt die Verbindungsstärke an. 



#### Einstellungen der Geräte-Instanz

##### Telefonnummer
Telefonnummer des Gerätes. (z.B. "+49 171 123456" )
Die Telefonnummer muss hierfür mit "+" und Ländervorwahl angegeben werden, da sonst der Vergleich der Nummer scheitert.
Die Leerzeichen und Bindestriche in der Telefonnummer werden herausgefiltert.

##### Pin
Pin-Nummer des GSM-Moduls. Die Pin-Nummer wird in jeder Nachricht zur Validierung versendet.

##### Übergeorndete Instanz
Als übergeordnete Instanz wird das Gateway ausgewählt 
 

#### Setzen des Ausgangs
```php
SMS_iSocketSetOutput(11111, true /* einschalten */);

```

#### Neustarten eines Ausgangs
Der ausgewählte Ausgang wird abgeschaltet 10 sekunden wieder eingeschaltet.  
```php
SMS_iSocketRestart(11111);

``` 
#### Abfragen des Status
```php
SMS_iSocketGetStatus(11111);

``` 

#### Logging für Statusvariablen aktivieren 
```php
SMS_EnableLogging(11111);
``` 

#### Logging für Statusvariablen deaktivieren 
```php
SMS_DisableLogging(11111);
``` 
