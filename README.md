# 2 Faktor Authentifizierung

2 Faktor Authentifizierung mittels one-time-password (OTP).
Mit diesem Addon wird der Login in das REDAXO CMS durch einen zweiten Authentifizierungsweg abgesichert.

![Screenshot](https://github.com/FriendsOfREDAXO/2factor_auth/blob/assets/screen.png?raw=true)


## Einrichtung

Als Authentifikator-Apps stehen alle Apps zur Verfügung, die den OTP-Standard einhalten, zum Beispiel:

Umgebung    | App                    | Hilfe
----------- |------------------------| -----
OSX/Windows | 1Password              | Kurzanleitung: https://support.1password.com/one-time-passwords/
iOS         | FreeOTP                | App: https://itunes.apple.com/de/app/freeotp-authenticator/id872559395, Kurzanleitung: https://support.1password.com/one-time-passwords/
Android     | FreeOTP                | App: https://play.google.com/store/apps/details?id=org.fedorahosted.freeotp
iOS         | Google Authentificator | App: https://itunes.apple.com/de/app/google-authenticator/id388497605
Android     | Google Authentificator | App: https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2
Android     | 2FAS Authenticator     | App: https://play.google.com/store/apps/details?id=com.twofasapp
iOS         | 2FAS Authenticator     | App: https://apps.apple.com/us/app/2fas-auth/id1217793794

Zunächst muss eine der Apps installiert sein, um die Einrichtung abzuschließen. Anschließend:

1. Im REDAXO-Backend > `2FA / 2 Faktor Authentifikation` öffnen.
2. Die 2-Faktor-Einrichtung aktivieren (gilt Systemweit für alle Nutzer). Es wird ein QR-Code dargestellt.
3. Den QR-Code in der Authentifikator-App einlesen. 

> Hinweis: Manche OTP-Apps benötigen den manuellen Modus. Hierbei gilt: Name = `Name der Website`; Benutzer = `REDAXO-Benutzername`; Secret = `Secret-Schlüssel`

Damit ist die Einrichtung abgeschlossen.

## Verwendung

Nach der erfolgreichen Einrichtung wird jeder neue Login in das REDAXO-Backend durch ein zusätzliches, einmalig generiertes Passwort geschützt.

1. Den REDAXO-Backend-Login aufrufen.
2. Mit den üblichen Zugangsdaten einloggen. Es wird nach dem einmaligen Zugangscode gefragt.
3. Die Authentifikator-App öffnen und den Zugangscode generieren lassen.
4. Diesen Code eingeben und fortfahren.

Anschließend ist man, wie gewohnt, im REDAXO-Backend eingeloggt.

## 💌 Give back some love

[Consider supporting the project](https://github.com/sponsors/staabm), so we can make this tool even better even faster for everyone.

## Autoren

**Markus Staab**  
https://github.com/staabm 
