# Deadline

Eine deutschsprachige To-do-Website für einen bestehenden PHP/MariaDB-Webserver. Kein Docker, Composer, Node.js oder Framework erforderlich.

## Auf deinem Server einrichten

Voraussetzungen: PHP **8.2 oder neuer** mit `PDO`, `pdo_mysql` und aktivierten Sessions sowie MariaDB **10.11 oder neuer**. Der Webserver muss PHP ausführen können, beispielsweise Apache mit PHP oder Nginx mit PHP-FPM.

1. Projekt auf deinen Server kopieren oder mit Git auschecken.
2. In deinem Hosting-Panel eine Datenbank und einen Datenbankbenutzer anlegen. Der Benutzer benötigt für diese Datenbank SELECT, INSERT, UPDATE und DELETE; für den einmaligen Schemaimport zusätzlich CREATE und INDEX.
3. `database/schema.sql` in diese Datenbank importieren, etwa über phpMyAdmin (Datenbank auswählen → Importieren) oder die Kommandozeile:

   ```sh
   mariadb -h 127.0.0.1 -u DEIN_BENUTZER -p DEINE_DATENBANK < database/schema.sql
   ```

   Der Import legt die Tabelle `todos` an und löscht keine bestehenden Einträge. Beim Import vorhandene gleichnamige Tabellen werden nicht aktualisiert.

4. Die Beispielkonfiguration kopieren:

   ```sh
   cp config/database.example.php config/database.php
   ```

   In **`config/database.php`** Host, Port, Datenbankname, Benutzer und Passwort eintragen:

   ```php
   <?php
   return [
       'host' => '127.0.0.1',
       'port' => 3306,
       'database' => 'DEINE_DATENBANK',
       'username' => 'DEIN_BENUTZER',
       'password' => 'DEIN_PASSWORT',
   ];
   ```

   Bei Passwörtern mit einfachen Anführungszeichen oder Backslashes müssen diese im PHP-String mit einem Backslash maskiert werden. Die Datei ist in Git ausgeschlossen und bleibt bei einem normalen `git pull` erhalten. Keine echten Zugangsdaten in die Beispielkonfiguration schreiben. Eine `.env` wird nicht benötigt und nicht eingelesen.

5. Das **DocumentRoot deiner Domain oder Subdomain auf `public/`** setzen, beispielsweise `/var/www/deadlinePage/public`. `config/`, `src/` und `database/` bleiben außerhalb des Webverzeichnisses. Nicht das gesamte Projekt in ein öffentliches Verzeichnis legen. Unterstützt dein Hosting kein frei wählbares DocumentRoot, kopiere nur den Inhalt von `public/` in das Webverzeichnis und lege `src/` und `config/` als dessen Nachbarverzeichnisse eine Ebene darüber ab. Der relative Pfad aus `public/index.php` zu `../src/bootstrap.php` muss dabei erhalten bleiben. Die Anwendung erwartet die URL-Wurzel einer Domain/Subdomain, keinen Unterordner.
6. Stelle sicher, dass der PHP-Prozess die Projektdateien und `config/database.php` lesen kann und sein Sessionverzeichnis beschreibbar ist. Das Projektverzeichnis selbst benötigt keine Schreibrechte für PHP. Die Konfigurationsdatei nur für die notwendigen Serverbenutzer lesbar machen.
7. Domain öffnen und das erste To-do anlegen. Bei Fehlern das PHP-Fehlerprotokoll des Servers prüfen; Zugangsdaten werden nicht auf der Website angezeigt. PHP-Fehlerausgabe (`display_errors`) auf einem öffentlich erreichbaren Server deaktivieren.

## MariaDB-Anbindung

PHP verbindet sich auch mit MariaDB über die Erweiterung `pdo_mysql` und einen `mysql:`-DSN. Das ist der korrekte Treibername; ein `mariadb:`-DSN wird von PDO nicht unterstützt. Host, Port und Zugangsdaten stehen weiterhin in `config/database.php`.

Das Schema verwendet InnoDB und `utf8mb4_unicode_ci`, die MariaDB direkt unterstützt. Bei einer neuen MariaDB-Datenbank genügt der Schemaimport. Bestehende Aufgaben aus einer anderen Datenbank werden dadurch nicht übertragen: zuerst die Tabelle `todos` samt Daten exportieren und in MariaDB importieren, anschließend die lokale Konfiguration auf MariaDB umstellen. Eine bestehende `todos`-Tabelle wird durch den Schemaimport nicht gelöscht oder verändert.

## Funktionen

- To-dos mit Titel, Notizen und frei wählbarer Farbe anlegen und bearbeiten.
- Start und Abgabetermin festlegen; ein optionaler Haupttermin darf gleich oder später liegen. Beispiel: Abgabe der Folien am Montag, Präsentation am Mittwoch.
- Der Balken zeigt den Anteil der verbleibenden Zeit im Zeitraum von Start bis Abgabe. Er aktualisiert sich alle 30 Sekunden. Überfällige Aufgaben haben einen leeren Balken und einen Hinweis.
- Als erledigt markieren, wieder öffnen, löschen, nach Titel suchen sowie offene/erledigte Aufgaben filtern.
- Termine werden in `Europe/Vienna` eingegeben und gespeichert; Browser erhalten explizite Zeitstempel zur Restzeitberechnung.

## Lokal entwickeln und prüfen

Mit lokal installiertem PHP und erreichbarer MariaDB-Datenbank nach der obigen Einrichtung aus dem Projektverzeichnis starten:

```sh
php -S 127.0.0.1:8080 -t public
```

Der eingebaute PHP-Server dient nur zur Entwicklung. Für den normalen Betrieb den bestehenden Webserver verwenden.

```sh
php -l config/database.php
php -l src/bootstrap.php
php -l public/index.php
python3 tests/smoke.py
```

Der optionale Integrationstest benötigt Python 3 und eine laufende Anwendung. Er erzeugt einen eindeutig benannten Testeintrag, prüft CRUD, Terminvalidierung, Farben, HTML-Escaping und CSRF-Schutz und löscht ihn wieder. Für einen anderen lokalen Testserver kann `TEST_BASE_URL` gesetzt werden, zum Beispiel `TEST_BASE_URL=http://127.0.0.1:8000 python3 tests/smoke.py`. Nicht gegen fremde oder produktive Instanzen testen.

## Betrieb

Die erste Version ist ein gemeinsamer Arbeitsbereich ohne Benutzerkonten. Nur lokal bzw. in einem vertrauenswürdigen Netz betreiben oder den Zugriff über den Webserver beschränken. Vor öffentlicher Bereitstellung sind Anmeldung/Zugriffsschutz, HTTPS und Datenbankbackups einzurichten. POST-Aktionen haben CSRF-Schutz, Datenbankabfragen verwenden Prepared Statements, Ausgaben werden HTML-escaped.

Ein Update ersetzt keine Datenbankdaten. `config/database.php` und die Datenbank unabhängig vom Git-Repository sichern. Die früheren Docker-Dateien wurden entfernt; vorhandene Docker-Datenvolumes werden dadurch nicht gelöscht. Bei einem Umzug von der früheren Docker-Installation zuerst die bestehende Datenbank exportieren und auf dem Server importieren.
