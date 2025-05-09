#!/bin/bash

# Skript zur Überprüfung des PostgreSQL-Verbindungsstatus in Orbstack

echo "===== PostgreSQL-Verbindungsstatus ====="
echo ""

# Prüfen, ob Docker läuft
if ! command -v docker &> /dev/null; then
    echo "❌ Docker ist nicht installiert oder nicht im PATH."
    exit 1
fi

# Prüfen, ob docker-compose verfügbar ist
if ! command -v docker-compose &> /dev/null; then
    echo "❌ docker-compose ist nicht installiert oder nicht im PATH."
    exit 1
fi

# Prüfen, ob PostgreSQL-Container läuft
PG_CONTAINER=$(docker ps --filter "name=db" --filter "ancestor=postgres" --format "{{.Names}}")

if [ -z "$PG_CONTAINER" ]; then
    echo "❌ PostgreSQL-Container ist nicht aktiv."
    echo "   Starte den Container mit: docker-compose up -d"
    exit 1
fi

echo "✅ PostgreSQL-Container läuft: $PG_CONTAINER"

# PostgreSQL-Version abrufen
PG_VERSION=$(docker exec $PG_CONTAINER postgres --version | awk '{print $3}')
echo "📊 PostgreSQL-Version: $PG_VERSION"

# Verbindungsinformationen abrufen
DB_HOST=$(docker inspect $PG_CONTAINER --format='{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}')
DB_PORT=$(docker inspect $PG_CONTAINER --format='{{range $p, $conf := .NetworkSettings.Ports}}{{if eq $p "5432/tcp"}}{{range $conf}}{{.HostPort}}{{end}}{{end}}{{end}}')

echo "📊 Container-IP: $DB_HOST"
echo "📊 Gemappter Port: $DB_PORT"

# Prüfen, ob der Port erreichbar ist
if command -v nc &> /dev/null; then
    if nc -z localhost $DB_PORT &> /dev/null; then
        echo "✅ PostgreSQL-Port ist erreichbar auf localhost:$DB_PORT"
    else
        echo "❌ PostgreSQL-Port ist NICHT erreichbar auf localhost:$DB_PORT"
    fi
fi

# Lokale IP-Adresse abrufen
LOCAL_IP=$(hostname -I | awk '{print $1}')
echo "📊 Lokale IP-Adresse: $LOCAL_IP"

# Verbindungsdetails anzeigen
echo ""
echo "===== Verbindungsdetails für dein Team ====="
echo "Host: $LOCAL_IP (lokale IP) oder deine öffentliche IP"
echo "Port: $DB_PORT"
echo "Datenbank: cervelingua"
echo "Benutzer: postgres"
echo "Passwort: macintosh"

# Prüfen, ob PostgreSQL für Remote-Verbindungen konfiguriert ist
echo ""
echo "===== Remote-Verbindungskonfiguration ====="

# listen_addresses prüfen
LISTEN_ADDRESSES=$(docker exec $PG_CONTAINER psql -U postgres -c "SHOW listen_addresses;" -t | tr -d ' ')
if [ "$LISTEN_ADDRESSES" = "*" ] || [[ "$LISTEN_ADDRESSES" == *","* ]]; then
    echo "✅ PostgreSQL akzeptiert Remote-Verbindungen (listen_addresses = $LISTEN_ADDRESSES)"
else
    echo "❌ PostgreSQL akzeptiert nur lokale Verbindungen (listen_addresses = $LISTEN_ADDRESSES)"
    echo "   Führe das Setup-Skript aus: ./scripts/setup_shared_postgres.sh"
fi

# pg_hba.conf prüfen
REMOTE_ACCESS=$(docker exec $PG_CONTAINER psql -U postgres -c "SELECT COUNT(*) FROM pg_hba_file_rules WHERE type = 'host' AND address = '0.0.0.0/0';" -t | tr -d ' ')
if [ "$REMOTE_ACCESS" -gt "0" ]; then
    echo "✅ PostgreSQL erlaubt Verbindungen von allen IP-Adressen"
else
    echo "❌ PostgreSQL erlaubt keine Verbindungen von externen IP-Adressen"
    echo "   Führe das Setup-Skript aus: ./scripts/setup_shared_postgres.sh"
fi

echo ""
echo "===== Nächste Schritte ====="
echo "1. Wenn Remote-Verbindungen nicht aktiviert sind, führe aus: ./scripts/setup_shared_postgres.sh"
echo "2. Stelle sicher, dass Port $DB_PORT in deiner Firewall geöffnet ist"
echo "3. Teile die Verbindungsdetails mit deinem Team"
echo "4. Weitere Informationen findest du in: docs/postgresql_sharing_guide.md"