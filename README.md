# Deadline

Eine deutschsprachige To-do-Website mit PHP 8.4 und MySQL 8.4. Ohne Framework, Composer oder externe Frontend-Abhängigkeiten.

## Starten

Voraussetzungen: Docker mit Compose. Im Repository:

```sh
cp .env.example .env
# In .env zwei unterschiedliche, zufällige Passwörter eintragen.
docker compose up -d --build
```

Die Anwendung läuft auf Port 8080, gebunden an die lokale Schnittstelle. MySQL ist ausschließlich im internen Compose-Netz erreichbar. Beim ersten Start wird das Schema automatisch angelegt. Daten bleiben im benannten Volume `mysql_data` erhalten.

```sh
docker compose ps
docker compose exec -T web php -l public/index.php
python3 tests/smoke.py
docker compose down
```

`docker compose down` erhält die Daten. `docker compose down -v` löscht sie unwiderruflich.

## Funktionen

- To-dos mit Titel, Notizen und frei wählbarer Farbe anlegen und bearbeiten.
- Start und Abgabetermin festlegen; ein optionaler Haupttermin darf gleich oder später liegen. Beispiel: Abgabe der Folien am Montag, Präsentation am Mittwoch.
- Der Balken zeigt den Anteil der verbleibenden Zeit im Zeitraum von Start bis Abgabe. Er aktualisiert sich alle 30 Sekunden. Überfällige Aufgaben haben einen leeren Balken und einen Hinweis.
- Als erledigt markieren, wieder öffnen, löschen, nach Titel suchen sowie offene/erledigte Aufgaben filtern.
- Termine werden in `Europe/Vienna` eingegeben und gespeichert; Browser erhalten explizite Zeitstempel zur Restzeitberechnung.

## Entwicklung und Betrieb

Der Quellcode wird im Entwicklungscontainer eingebunden; Änderungen sind direkt sichtbar. Web-Einstiegspunkt ist ausschließlich `public/`. Die Datenbankverbindung verwendet `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` (siehe Compose). Keine Zugangsdaten committen.

Die erste Version ist ein gemeinsamer Arbeitsbereich ohne Benutzerkonten. Nur lokal bzw. in einem vertrauenswürdigen Netz betreiben. Vor öffentlicher Bereitstellung sind Anmeldung, Zugriffsschutz, HTTPS und Backups einzurichten. POST-Aktionen haben CSRF-Schutz, Datenbankabfragen verwenden Prepared Statements, Ausgaben werden HTML-escaped.

Der Integrationstest erzeugt einen eindeutig benannten Testeintrag, prüft CRUD, Terminvalidierung, Farben, HTML-Escaping und CSRF-Schutz und löscht ihn wieder. Er benötigt den laufenden Stack und Python 3. Browser-Interaktionen lassen sich zusätzlich manuell mit dem Dialog und den Filtern prüfen.
