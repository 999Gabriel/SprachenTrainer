@echo off
REM Skript zur Überprüfung des PostgreSQL-Verbindungsstatus für Windows mit Docker

echo ===== PostgreSQL-Verbindungsstatus =====
echo.

REM Prüfen, ob Docker läuft
docker --version > nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ❌ Docker ist nicht installiert oder nicht im PATH.
    exit /b 1
)

REM Prüfen, ob docker-compose verfügbar ist
docker-compose --version > nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ❌ docker-compose ist nicht installiert oder nicht im PATH.
    exit /b 1
)

REM Prüfen, ob PostgreSQL-Container läuft
FOR /F "tokens=*" %%i IN ('docker ps --filter "name=db" --filter "ancestor=postgres" --format "{{.Names}}"') DO SET PG_CONTAINER=%%i

if "%PG_CONTAINER%"=="" (
    echo ❌ PostgreSQL-Container ist nicht aktiv.
    echo    Starte den Container mit: docker-compose up -d
    exit /b 1
)

echo ✅ PostgreSQL-Container läuft: %PG_CONTAINER%

REM PostgreSQL-Version abrufen
FOR /F "tokens=3" %%i IN ('docker exec %PG_CONTAINER% postgres --version') DO SET PG_VERSION=%%i
echo 📊 PostgreSQL-Version: %PG_VERSION%

REM Verbindungsinformationen abrufen
FOR /F "tokens=*" %%i IN ('docker inspect %PG_CONTAINER% --format="{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}"') DO SET DB_HOST=%%i
FOR /F "tokens=*" %%i IN ('docker inspect %PG_CONTAINER% --format="{{range $p, $conf := .NetworkSettings.Ports}}{{if eq $p \"5432/tcp\"}}{{range $conf}}{{.HostPort}}{{end}}{{end}}{{end}}"') DO SET DB_PORT=%%i

echo 📊 Container-IP: %DB_HOST%
echo 📊 Gemappter Port: %DB_PORT%

REM Lokale IP-Adresse abrufen
FOR /F "tokens=*" %%i IN ('ipconfig ^| findstr /C:"IPv4" ^| findstr /V "169.254"') DO SET IP_LINE=%%i
FOR /F "tokens=2 delims=:" %%i IN ("%IP_LINE%") DO SET LOCAL_IP=%%i
SET LOCAL_IP=%LOCAL_IP:~1%
echo 📊 Lokale IP-Adresse: %LOCAL_IP%

REM Verbindungsdetails anzeigen
echo.
echo ===== Verbindungsdetails für dein Team =====
echo Host: %LOCAL_IP% (lokale IP) oder deine öffentliche IP
echo Port: %DB_PORT%
echo Datenbank: cervelingua
echo Benutzer: postgres
echo Passwort: macintosh

REM Prüfen, ob PostgreSQL für Remote-Verbindungen konfiguriert ist
echo.
echo ===== Remote-Verbindungskonfiguration =====

REM listen_addresses prüfen
FOR /F "tokens=*" %%i IN ('docker exec %PG_CONTAINER% psql -U postgres -c "SHOW listen_addresses;" -t') DO SET LISTEN_ADDRESSES=%%i
SET LISTEN_ADDRESSES=%LISTEN_ADDRESSES: =%

if "%LISTEN_ADDRESSES%"=="*" (
    echo ✅ PostgreSQL akzeptiert Remote-Verbindungen (listen_addresses = %LISTEN_ADDRESSES%)
) else (
    echo ❌ PostgreSQL akzeptiert nur lokale Verbindungen (listen_addresses = %LISTEN_ADDRESSES%)
    echo    Führe das Setup-Skript aus: scripts\setup_shared_postgres_windows.bat
)

REM pg_hba.conf prüfen
FOR /F "tokens=*" %%i IN ('docker exec %PG_CONTAINER% psql -U postgres -c "SELECT COUNT(*) FROM pg_hba_file_rules WHERE type = ^'host^' AND address = ^'0.0.0.0/0^';" -t') DO SET REMOTE_ACCESS=%%i
SET REMOTE_ACCESS=%REMOTE_ACCESS: =%

if %REMOTE_ACCESS% GTR 0 (
    echo ✅ PostgreSQL erlaubt Verbindungen von allen IP-Adressen
) else (
    echo ❌ PostgreSQL erlaubt keine Verbindungen von externen IP-Adressen
    echo    Führe das Setup-Skript aus: scripts\setup_shared_postgres_windows.bat
)

echo.
echo ===== Nächste Schritte =====
echo 1. Wenn Remote-Verbindungen nicht aktiviert sind, führe aus: scripts\setup_shared_postgres_windows.bat
echo 2. Stelle sicher, dass Port %DB_PORT% in deiner Windows-Firewall geöffnet ist
echo 3. Teile die Verbindungsdetails mit deinem Team
echo 4. Weitere Informationen findest du in: docs\postgresql_sharing_guide_windows.md

pause