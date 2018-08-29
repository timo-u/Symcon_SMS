### SMSDevice

Das SMS-Device Stellt ein beliebiges Gerät mit SMS-Kommuniaktion dar. Eine vom Gateway empfangene Nachricht wird bei richtiger Telefonnummer auf das SMSDevice weiter geleitet. 
Im SMSDevice kann ein Script Hinterlegt werden was beim Empfang einer SMS ausgeführt wird. 


#### Einstellungen der Geräte-Instanz

##### Telefonnummer
Telefonnummer des Gerätes. (z.B. "+49 171 123456" )
Die Telefonnummer muss hierfür mit "+" und Ländervorwahl angegeben werden, da sonst der Vergleich der Nummer scheitert.
Die Leerzeichen und Bindestriche in der Telefonnummer werden herausgefiltert.

##### Skript
Das ausgewählte Script wird beim Empfangen einer SMS mit der Telefonnummer der Instanz aufgerufen. Hierbei wird die Nummer und der Text als Parameter an das Script übergeben. 


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

