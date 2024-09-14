# 2-Faktor-Authentifizierung

2-Faktor-Authentifizierung mittels one-time-password (OTP).
Mit diesem Addon wird der Login in das REDAXO CMS durch einen zweiten Authentifizierungsweg abgesichert.

![2-Faktor-Authentifizierung Weboberfläche](https://github.com/FriendsOfREDAXO/2factor_auth/blob/assets/screen.png?raw=true)

## Einstellungen

Der Administrator hat die Möglichkeit unter `Einstellungen` innerhalb des AddOns die 2-Faktor-Authentifizierung für die Benutzer zu erzwingen.
Alternativ kann die 2-Faktor-Authentifizierung als Optional gehandhabt werden. Die Authentifizierungsoptionen können eingeschränkt werden, z.B. Ausschließlich E-Mail-Authentifizierung zu erlauben

## Einrichtung Endbenutzer

Als Authentifikator-Apps stehen alle Apps zur Verfügung, die den OTP-Standard einhalten, zum Beispiel:

Umgebung                  | App                       | Hilfe
------------------------- |---------------------------| -----
MacOS/Windows/Android/iOS | 1Password                 | Kurzanleitung: <https://support.1password.com/one-time-passwords/>
iOS                       | FreeOTP                   | App: <https://apps.apple.com/de/app/freeotp-authenticator/id872559395>, Kurzanleitung: <https://support.1password.com/one-time-passwords/>
Android                   | FreeOTP                   | App: <https://play.google.com/store/apps/details?id=org.fedorahosted.freeotp>
Android                   | Microsoft Authentificator | App: <https://play.google.com/store/apps/details?id=com.azure.authenticator&hl=de>
iOS                       | Microsoft Authentificator | App: <https://apps.apple.com/de/app/microsoft-authenticator/id983156458>
iOS                       | Google Authentificator    | App: <https://apps.apple.com/de/app/google-authenticator/id388497605>
Android                   | Google Authentificator    | App: <https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2>
Android                   | 2FAS Authenticator        | App: <https://play.google.com/store/apps/details?id=com.twofasapp>
iOS                       | 2FAS Authenticator        | App: <https://apps.apple.com/us/app/2fas-auth/id1217793794>

Zunächst muss eine der Apps installiert sein, um die Einrichtung abzuschließen. Anschließend:

1. Im REDAXO-Backend > `2-Faktor-Login` öffnen.
2. Die 2-Faktor-Einrichtung aktivieren. Es wird ein QR-Code dargestellt.
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

## Credits

**Markus Staab**  
https://github.com/staabm 

**Jan Kristinus**  
https://github.com/dergel
