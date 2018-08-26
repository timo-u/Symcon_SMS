# Symcon_SMS

##Dieses Modul bietet erweiterte SMS-Funktionlitäten für IP-Symcon. 


### Teltonika SMS Gateway  

Zur Umsetzung der SMS wird ein Teltonika Router verwendet. Erstellt und getestet wurde dieses Modul mit dem Teltonika RUT955. Dieses Modul sollte jedoch auch  mit den Routern RUT950 und RUT240 funktionieren (Erfahrungsberichte nehme ich gerne entgegen. 
Das Gateway wird als I/O- Instanz in IP-Symcon eingebunden. 

#### Einstellungen der I/O-Instanz

##### Benutzername
Benutzername für POST/GET Anfragen (*Nicht die Zugangsdaten zum Router!*)

##### Passwort
Passwort für POST/GET Anfragen (*Nicht die Zugangsdaten zum Router!*)

##### IP-Adresse
URL zum Router inklusive Protokoll und Port (z.B. "http://192.168.1.1:80")

##### Abfrage-Intervall
In diesem Intervall werden nie Nachrichten aus dem Speicher des Routers abgerufen werden. Der Router kann bis zu 10 Nachrichten zwischenspeichern. 
Das Intervall kann von 1 Sekunde bis 5 Minunten eigestellt oder deaktiviert werden. 

##### Senden unterdrücken
Wenn das Feld aktiviert ist wird das Senden von SMS blockiert. Unterdrückte SMS werden im LOG angezeigt. 




#### Senden von Nachrichten
```php
RUT_SendMessage(1111 /*[Teltonika RUT955 SMS Gateway]*/, "+49 176 123456", "Lorem ipsum dolor sit amet" );
```


### SMSDevice

Das SMS-Device Stellt ein beliebiges Gerät mit SMS-Kommuniaktion dar. Eine vom Gateway empfangene Nachricht wird bei richtiger Telefonnummer auf das SMSDevice weiter geleitet. Im SMSDevice kann ein Script Hinterlegt werden was beim Empfang einer SMS ausgeführt wird. 


#### Einstellungen der Geräte-Instanz

##### Telefonnummer
Telefonnummer des Gerätes. (z.B. "+49 171 123456" )
Die Telefonnummer muss hierfür mit "+" und Ländervorwahl angegeben werden, da sonst der Vergleich der Nummer scheitert


##### Skript
Das ausgewählte Script wird beim empfangen einer SMS mit der Telefonnummer der Instanz aufgerufen. Hierbei wird die Nummer und der Text als Parameter an das Script übergeben. 

Die Informationen können im Script abgerufen werden: 
```php
<?
Echo "Sender: " . $_IPS['sender'] .  " Text: " . $_IPS['text'];
?>
```


##### Übergeorndete Instanz
Als übergeordnete Instanz wird das Gateway ausgewählt 


 

#### Senden von Nachrichten
```php
SMS_SendMessage(11111 /*[Test\SMSDevice]*/, "Lorem ipsum dolor sit amet");
```
Eine Nummer ist nicht nötig, da diese bereits in der Instanz hinterlegt ist.

