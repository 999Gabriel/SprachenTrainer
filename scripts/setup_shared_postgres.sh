#!/bin/bash

# Setup-Skript für die gemeinsame Nutzung der PostgreSQL-Datenbank in Orbstack
# Dieses Skript erstellt die notwendigen Konfigurationsdateien und aktualisiert docker-compose.yml

echo "PostgreSQL für gemeinsame Nutzung konfigurieren..."

# Verzeichnis für PostgreSQL-Konfiguration erstellen
mkdir -p ./postgres-config

# postgresql.conf erstellen
cat > ./postgres-config/postgresql.conf << EOF
listen_addresses = '*'
max_connections = 100
shared_buffers = 128MB
log_destination = 'stderr'
logging_collector = on
log_directory = 'log'
log_filename = 'postgresql-%Y-%m-%d_%H%M%S.log'
log_truncate_on_rotation = on
log_rotation_age = 1d
log_rotation_size = 10MB
client_min_messages = notice
log_min_messages = warning
log_min_error_statement = error
log_min_duration_statement = 1000
log_connections = on
log_disconnections = on
log_duration = off
log_line_prefix = '%m [%p] %q%u@%d '
log_timezone = 'Europe/Berlin'
EOF

# pg_hba.conf erstellen
cat > ./postgres-config/pg_hba.conf << EOF
# TYPE  DATABASE        USER            ADDRESS                 METHOD

# "local" is for Unix domain socket connections only
local   all             all                                     trust
# IPv4 local connections:
host    all             all             127.0.0.1/32           md5
# IPv4 remote connections:
host    all             all             0.0.0.0/0              md5
# IPv6 local connections:
host    all             all             ::1/128                 md5
# IPv6 remote connections:
host    all             all             ::/0                    md5
EOF

echo "PostgreSQL-Konfigurationsdateien erstellt."

# Prüfen, ob docker-compose.yml existiert
if [ -f "docker-compose.yml" ]; then
    # Backup der aktuellen docker-compose.yml erstellen
    cp docker-compose.yml docker-compose.yml.bak
    echo "Backup der docker-compose.yml erstellt: docker-compose.yml.bak"
    
    # Prüfen, ob die Volumes-Einträge bereits vorhanden sind
    if grep -q "./postgres-config/postgresql.conf:/etc/postgresql/postgresql.conf" docker-compose.yml; then
        echo "Die PostgreSQL-Konfiguration ist bereits in docker-compose.yml eingebunden."
    else
        # Temporäre Datei erstellen mit aktualisierten Volumes
        awk '{
            print $0;
            if ($0 ~ /volumes:/ && $0 ~ /db:/) {
                print "      - ./postgres-config/postgresql.conf:/etc/postgresql/postgresql.conf";
                print "      - ./postgres-config/pg_hba.conf:/etc/postgresql/pg_hba.conf";
            }
        }' docker-compose.yml > docker-compose.yml.new
        
        # Prüfen, ob command bereits vorhanden ist
        if grep -q "command:" docker-compose.yml; then
            echo "Der command-Eintrag ist bereits in docker-compose.yml vorhanden."
        else
            # command-Eintrag hinzufügen
            awk '{
                print $0;
                if ($0 ~ /restart: unless-stopped/ && $0 ~ /db:/) {
                    print "    command: postgres -c config_file=/etc/postgresql/postgresql.conf";
                }
            }' docker-compose.yml.new > docker-compose.yml.tmp
            mv docker-compose.yml.tmp docker-compose.yml.new
        fi
        
        # Neue Datei übernehmen
        mv docker-compose.yml.new docker-compose.yml
        echo "docker-compose.yml wurde aktualisiert."
    fi
else
    echo "WARNUNG: docker-compose.yml nicht gefunden. Bitte füge die Konfiguration manuell hinzu."
fi

echo ""
echo "Konfiguration abgeschlossen. Führe folgende Schritte aus, um die Änderungen zu übernehmen:"
echo "1. Starte die Docker-Container neu: docker-compose down && docker-compose up -d"
echo "2. Prüfe, ob PostgreSQL korrekt läuft: docker-compose logs db"
echo ""
echo "Verbindungsinformationen für dein Team:"
echo "- Host: $(hostname -I | awk '{print $1}') (lokale IP) oder deine öffentliche IP"
echo "- Port: 5432"
echo "- Datenbank: cervelingua"
echo "- Benutzer: postgres"
echo "- Passwort: macintosh"
echo ""
echo "WICHTIG: Aus Sicherheitsgründen solltest du das Passwort ändern, bevor du die Datenbank teilst."
echo "Weitere Informationen findest du in der Dokumentation: docs/postgresql_sharing_guide.md"