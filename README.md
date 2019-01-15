# 2-Faktor-Authentifizierung für REDAXO CMS >5.7
🐣 2-Faktor-Authentifizierung mittels one-time-password (OTP)

Mit diesem Addon wird der Login in das REDAXO durch einen zweiten Authentifizierungsweg abgesichert.

## Installation

1. Unter https://github.com/FriendsOfREDAXO/2factor_auth/ Download als ZIP-Datei
2. In `/redaxo/src/addons/` kopieren und von `2factor_auth-master` in `2factor_auth` umbennenn
3. Im REDAXO-Backend unter AddOns installieren und aktivieren

> Das Addon befindet sich in der Entwicklung. Wenn das Addon releasefähig ist, wird es auch im Installer angeboten.

## Einrichtung

Als Authentifikator-Apps stehen alle Apps zur Verfügung, die den OTP-Standard einhalten, z.B.

Umgebung    | App                    | Hilfe
----------- | ---------------------- | -----
OSX/Windows | 1Password              | Kurzanleitung: https://support.1password.com/one-time-passwords/
iOS         | FreeOTP                | App: https://itunes.apple.com/de/app/freeotp-authenticator/id872559395, Kurzanleitung: https://support.1password.com/one-time-passwords/
Android     | FreeOTP                | App: https://play.google.com/store/apps/details?id=org.fedorahosted.freeotp
iOS         | Google Authentificator | App: https://itunes.apple.com/de/app/google-authenticator/id388497605
Android     | Google Authentificator | App: https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2

Zunächst muss eine der Apps installiert sein, um die Einrichtung abzuschließen. Anschließend:

1. Im REDAXO-Backend > `2 Faktor Setup` öffnen.
2. Die 2-Faktor-Einrichtung aktivieren. Es wird ein QR-Code dargestellt.
3. Den QR-Code in der Authentifikator-App einlesen. 

Damit ist die Einrichtung abgeschlossen.

## Verwendung

Nach der erfolgreichen Einrichtung wird jeder neue Login in das REDAXO-Backend durch ein zusätzliches, einmalig generiertes Passwort geschützt.

1. Den REDAXO-Backend-Login aufrufen.
2. Mit den üblichen Zugangsdaten einloggen. Es wird nach dem einmaligen Zugangscode gefragt.
3. Die Authentifikator-App öffnen und den Zugangscode generieren lassen.
4. Diesen Code eingeben und fortfahren.

Anschließend ist man, wie gewohnt, im REDAXO-Backend eingeloggt.

status: https://github.com/FriendsOfREDAXO/2factor_auth/issues/1
