# PostgreSQL-Datenbank mit mehreren Personen teilen (Windows mit Docker)

Diese Anleitung beschreibt, wie du deine PostgreSQL-Datenbank, die in Docker unter Windows läuft, mit mehreren Personen teilen kannst.

## Voraussetzungen

- Windows 10/11 mit installiertem Docker Desktop
- Docker Compose
- Administratorrechte für Firewall-Einstellungen

## Aktuelle Konfiguration

Deine PostgreSQL-Datenbank läuft derzeit mit folgenden Einstellungen:

- **Host**: `db` (Docker-Service-Name) oder `localhost` (von außerhalb des Containers)
- **Port**: `5432` (Standard-PostgreSQL-Port, gemappt auf Host-Port 5432)
- **Datenbank**: `cervelingua`
- **Benutzer**: `postgres`
- **Passwort**: `macintosh`

## Schritt-für-Schritt-Anleitung

### Schritt 1: Status prüfen

Überprüfe zuerst den aktuellen Status deiner PostgreSQL-Datenbank:

1. Öffne eine Eingabeaufforderung (CMD) oder PowerShell
2. Navigiere zum Projektverzeichnis
3. Führe das Status-Skript aus:

```cmd
scripts\check_postgres_status_windows.bat
```

Dieses Skript zeigt dir, ob dein PostgreSQL-Container läuft und ob er bereits für Remote-Verbindungen konfiguriert ist.

### Schritt 2: PostgreSQL für Remote-Verbindungen konfigurieren

Wenn das Status-Skript anzeigt, dass Remote-Verbindungen nicht aktiviert sind, führe das Setup-Skript aus:

```cmd
scripts\setup_shared_postgres_windows.bat
```

Dieses Skript wird:

1. Die notwendigen Konfigurationsdateien erstellen
2. Anweisungen geben, wie du deine `docker-compose.yml` anpassen musst
3. Die Container neu starten, um die Änderungen zu übernehmen

### Schritt 3: Windows-Firewall konfigurieren

Damit andere auf deine Datenbank zugreifen können, musst du Port 5432 in der Windows-Firewall freigeben:

1. Öffne die Windows-Firewall (Systemsteuerung → System und Sicherheit → Windows-Firewall)
2. Klicke auf "Erweiterte Einstellungen"
3. Wähle "Eingehende Regeln" und klicke auf "Neue Regel..."
4. Wähle "Port" und klicke auf "Weiter"
5. Wähle "TCP" und gib "5432" als Port ein
6. Wähle "Verbindung zulassen" und klicke auf "Weiter"
7. Aktiviere alle Netzwerktypen (Domain, Privat, Öffentlich)
8. Gib einen Namen ein (z.B. "PostgreSQL") und klicke auf "Fertig stellen"

### Schritt 4: Verbindungsinformationen teilen

Teile folgende Informationen mit deinem Team:

- **Host**: Deine IP-Adresse (wird im Status-Skript angezeigt)
- **Port**: 5432
- **Datenbank**: cervelingua
- **Benutzer**: postgres
- **Passwort**: macintosh

## Verbindung testen

Teammitglieder können ihre Verbindung mit folgendem Befehl testen:

```cmd
psql -h <deine-ip-adresse> -p 5432 -U postgres -d cervelingua
```

Oder mit einem GUI-Tool wie pgAdmin, DBeaver oder TablePlus.

## Sicherheitshinweise

1. **Ändere das Standard-Passwort**: Das aktuelle Passwort 'macintosh' sollte in einer Produktionsumgebung geändert werden.

2. **Netzwerksicherheit**: Beschränke den Zugriff auf vertrauenswürdige IP-Adressen, wenn möglich.

3. **Temporärer Zugriff**: Wenn du die Datenbank nur temporär teilen möchtest, deaktiviere die Firewall-Regel nach der Zusammenarbeit.

## Fehlerbehebung

### Container startet nicht

Wenn der PostgreSQL-Container nicht startet, prüfe die Docker-Logs:

```cmd
docker-compose logs db
```

### Verbindungsprobleme

1. **Firewall-Probleme**: Stelle sicher, dass die Windows-Firewall den Zugriff auf Port 5432 erlaubt.

2. **Docker-Netzwerk**: Stelle sicher, dass dein Docker-Netzwerk korrekt konfiguriert ist.

3. **IP-Adresse**: Verwende die korrekte IP-Adresse. Wenn du im gleichen Netzwerk bist, verwende die lokale IP. Für Verbindungen über das Internet benötigst du deine öffentliche IP und eine entsprechende Port-Weiterleitung in deinem Router.

### Konfigurationsprobleme

Wenn die Konfiguration nicht korrekt übernommen wurde, prüfe:

1. Ob die Konfigurationsdateien im Verzeichnis `postgres-config` existieren
2. Ob die `docker-compose.yml` korrekt angepasst wurde
3. Ob der Container nach den Änderungen neu gestartet wurde

## Unterschiede zu macOS/Linux

Diese Windows-Anleitung unterscheidet sich in folgenden Punkten von der macOS/Linux-Version:

1. Verwendung von Batch-Skripten (`.bat`) statt Shell-Skripten (`.sh`)
2. Windows-spezifische Befehle für die IP-Adressermittlung
3. Windows-Firewall-Konfiguration statt iptables/ufw
4. Pfadangaben mit Backslash (`\`) statt Forward-Slash (`/`)

## Weitere Ressourcen

- [Docker Desktop für Windows Dokumentation](https://docs.docker.com/desktop/windows/)
- [PostgreSQL Dokumentation](https://www.postgresql.org/docs/)
- [Windows-Firewall-Dokumentation](https://docs.microsoft.com/de-de/windows/security/threat-protection/windows-firewall/windows-firewall-with-advanced-security)