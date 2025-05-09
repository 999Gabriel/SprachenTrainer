# PostgreSQL-Datenbank mit mehreren Personen teilen (Orbstack)

Diese Anleitung beschreibt, wie du deine PostgreSQL-Datenbank, die in Orbstack mit Docker läuft, mit mehreren Personen teilen kannst.

## Aktuelle Konfiguration

Deine PostgreSQL-Datenbank läuft derzeit mit folgenden Einstellungen:

- **Host**: `db` (Docker-Service-Name) oder `localhost` (von außerhalb des Containers)
- **Port**: `5432` (Standard-PostgreSQL-Port, gemappt auf Host-Port 5432)
- **Datenbank**: `cervelingua`
- **Benutzer**: `postgres`
- **Passwort**: `macintosh`

## Optionen zum Teilen der Datenbank

### Option 1: Direkter Zugriff über Netzwerk (für lokales Team)

#### Schritt 1: PostgreSQL für Remote-Verbindungen konfigurieren

Bearbeite die PostgreSQL-Konfiguration, um Remote-Verbindungen zu erlauben:

1. Erstelle eine benutzerdefinierte `postgresql.conf`-Datei im Projektverzeichnis:

```bash
mkdir -p ./postgres-config
touch ./postgres-config/postgresql.conf
```

2. Füge folgende Zeilen in die `postgresql.conf` ein:

```
listen_addresses = '*'
max_connections = 100
```

3. Erstelle eine `pg_hba.conf`-Datei für die Zugriffssteuerung:

```bash
touch ./postgres-config/pg_hba.conf
```

4. Füge folgende Zeilen in die `pg_hba.conf` ein:

```
# TYPE  DATABASE        USER            ADDRESS                 METHOD
host    all             all             0.0.0.0/0               md5
host    all             all             ::/0                    md5
```

5. Aktualisiere deine `docker-compose.yml`, um diese Konfigurationsdateien einzubinden:

```yaml
db:
  image: postgres:17
  environment:
    - POSTGRES_DB=cervelingua
    - POSTGRES_USER=postgres
    - POSTGRES_PASSWORD=macintosh
  ports:
    - "5432:5432"
  volumes:
    - postgres_data:/var/lib/postgresql/data
    - ./postgres-config/postgresql.conf:/etc/postgresql/postgresql.conf
    - ./postgres-config/pg_hba.conf:/etc/postgresql/pg_hba.conf
  command: postgres -c config_file=/etc/postgresql/postgresql.conf
  restart: unless-stopped
```

#### Schritt 2: Firewall-Einstellungen

Stelle sicher, dass Port 5432 in deiner Firewall geöffnet ist, wenn Teammitglieder von außerhalb deines lokalen Netzwerks zugreifen müssen.

#### Schritt 3: Verbindungsinformationen teilen

Teile folgende Informationen mit deinem Team:

- **Host**: Deine IP-Adresse oder Hostname
- **Port**: 5432
- **Datenbank**: cervelingua
- **Benutzer**: postgres
- **Passwort**: macintosh

### Option 2: Temporärer Zugriff mit ngrok (für Remote-Teams)

Wenn dein Team remote arbeitet, kannst du ngrok verwenden, um temporären Zugriff zu ermöglichen:

1. Installiere ngrok: https://ngrok.com/download

2. Starte einen Tunnel zu deinem PostgreSQL-Port:

```bash
ngrok tcp 5432
```

3. Teile die von ngrok generierte URL mit deinem Team (z.B. `0.tcp.ngrok.io:12345`).

### Option 3: Dedizierte Benutzer erstellen (empfohlen für Produktionsumgebungen)

Für mehr Sicherheit solltest du dedizierte Benutzer mit eingeschränkten Rechten erstellen:

1. Verbinde dich mit der PostgreSQL-Datenbank:

```bash
docker exec -it antwortentrainer-db-1 psql -U postgres -d cervelingua
```

2. Erstelle einen neuen Benutzer mit eingeschränkten Rechten:

```sql
CREATE USER teammitglied WITH PASSWORD 'sicheres_passwort';
GRANT CONNECT ON DATABASE cervelingua TO teammitglied;
GRANT USAGE ON SCHEMA public TO teammitglied;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO teammitglied;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO teammitglied;
```

## Sicherheitshinweise

1. **Ändere das Standard-Passwort**: Das aktuelle Passwort 'macintosh' sollte in einer Produktionsumgebung geändert werden.

2. **Verwende SSL**: Für Produktionsumgebungen solltest du SSL für PostgreSQL-Verbindungen aktivieren.

3. **Netzwerksicherheit**: Beschränke den Zugriff auf vertrauenswürdige IP-Adressen, wenn möglich.

4. **Regelmäßige Backups**: Stelle sicher, dass du regelmäßige Backups deiner Datenbank erstellst.

## Verbindung testen

Teammitglieder können ihre Verbindung mit folgendem Befehl testen:

```bash
psql -h <host> -p <port> -U postgres -d cervelingua
```

Oder mit einem GUI-Tool wie pgAdmin, DBeaver oder TablePlus.

## Fehlerbehebung

1. **Verbindungsprobleme**: Stelle sicher, dass der Port 5432 nicht von einer anderen Anwendung verwendet wird.

2. **Firewall-Probleme**: Überprüfe, ob deine Firewall den Zugriff auf Port 5432 erlaubt.

3. **Docker-Netzwerk**: Stelle sicher, dass dein Docker-Netzwerk korrekt konfiguriert ist.

4. **Logs prüfen**: Überprüfe die PostgreSQL-Logs mit `docker logs antwortentrainer-db-1`.