### Teltonika SMS Gateway

Zur Umsetzung der SMS wird ein Teltonika Router als SMS-Gateway verwendet. Erstellt und getestet wurde dieses Modul mit dem Teltonika RUT360 auf dem Firmwarestand 00.07.14.3. Dieses Modul sollte jedeoch mit der gesamten Router-Linie von Teltonika funkioniernen, die die SMS-Gateway-Funktion besitzen und aud der Firmware 00.07.xx.xx laufen.

Das Gateway wird als I/O- Instanz in IP-Symcon eingebunden. 

Um die "alte implementierung" vor Version 7.0 zu verwendengibt es die Instanz "Teltonika SMS Gateway Legacy". 

#### Einstellungen der I/O-Instanz

##### Primär


###### URL
URL zum Gateway inklusive Protokoll und Port (z.B. "http://192.168.1.1:443")
Achtung ab Softwarestand 7.12 wird nur noch eine verschlüsselte Verbindung akzeptiert.

###### Benutzername
Benutzername zum Gateway. (Administrator oder anderer Benutzer mit Berechtigung für den SMS-Versand)

###### Passwort
Passwort zum Benutzer 

##### Verschlüsselungseinstellungen
##### SSL
Schaltet auf eine verschlüsselte Verbindung (HTTPS) um.

###### Server Überprüfen
Prüft ob das Zertifikat zum Server passt

###### Zertifikat Überprüfen
Prüft die Zertifikatskette (Bei selbstsigniertem Zertifikat deaktivieren)

###### Abfrage-Intervall
In diesem Intervall werden nie Nachrichten aus dem Speicher des Routers abgerufen werden.
Das Intervall kann von 1 Sekunde bis 5 Minunten eigestellt oder deaktiviert werden. 
Über einen Webhook kann das Gateway eine neue Nachricht signalisieren. 


##### Sektrundär
Wenn ein zweites Gateway zu Redundanzzwecken verwendet werden soll können hier die Zugangsdaten analog zum primären Router hinterlegt werden. 

###### Sektrundär Gateway verwenden
Wenn dieses Feld aktiviert ist wird ein zweiter Router verwendet. 

##### Modus
Hier kann ausgewählt wie die ausgehenden Nachrichten auf die Gateways verteilt werden. 

##### Senden unterdrücken
Wenn das Feld aktiviert ist wird das Senden von SMS blockiert. Unterdrückte SMS werden im LOG angezeigt. 





#### Senden von Nachrichten
```php
SMS_GatewaySendMessage(1111 /*[Teltonika SMS Gateway]*/, "+49 176 123456", "Lorem ipsum dolor sit amet" );
```

#### Senden von Nachrichten über bestimtes Gatewy
```php
SMS_GatewaySendMessage(1111 /*[Teltonika RUT955 SMS Gateway]*/, "+49 176 123456", "Lorem ipsum dolor sit amet" ,1 /*Modus 1*/ ,1 /* ein Sendeversuch"/);
```

#### Manuelles Abrufen von Nachrichten
```php
SMS_GetMessage(1111 /*[Teltonika SMS Gateway]*/);
```