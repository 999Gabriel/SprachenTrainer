@echo off
REM Skript zur Konfiguration von PostgreSQL für Remote-Verbindungen unter Windows

echo ===== PostgreSQL für Remote-Verbindungen konfigurieren =====
echo.

REM Prüfen, ob Docker läuft
docker --version > nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ❌ Docker ist nicht installiert oder nicht im PATH.
    echo.
    pause
    exit /b 1
)

REM Prüfen, ob docker-compose verfügbar ist
docker-compose --version > nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ❌ docker-compose ist nicht installiert oder nicht im PATH.
    echo.
    pause
    exit /b 1
)

REM Verzeichnis für PostgreSQL-Konfiguration erstellen
if not exist "postgres-config" (
    mkdir postgres-config
    echo ✅ Verzeichnis postgres-config erstellt.
) else (
    echo ℹ️ Verzeichnis postgres-config existiert bereits.
)

REM postgresql.conf erstellen
echo listen_addresses = '*' > postgres-config\postgresql.conf
echo max_connections = 100 >> postgres-config\postgresql.conf

REM pg_hba.conf erstellen
echo # TYPE  DATABASE        USER            ADDRESS                 METHOD > postgres-config\pg_hba.conf
echo # IPv4 local connections: >> postgres-config\pg_hba.conf
echo host    all             all             127.0.0.1/32            md5 >> postgres-config\pg_hba.conf
echo # IPv4 remote connections: >> postgres-config\pg_hba.conf
echo host    all             all             0.0.0.0/0               md5 >> postgres-config\pg_hba.conf
echo # IPv6 local connections: >> postgres-config\pg_hba.conf
echo host    all             all             ::1/128                 md5 >> postgres-config\pg_hba.conf
echo # IPv6 remote connections: >> postgres-config\pg_hba.conf
echo host    all             all             ::/0                    md5 >> postgres-config\pg_hba.conf

echo ✅ PostgreSQL-Konfigurationsdateien erstellt.

REM Prüfen, ob docker-compose.yml existiert
if exist "docker-compose.yml" (
    REM Backup der aktuellen docker-compose.yml erstellen
    copy docker-compose.yml docker-compose.yml.bak
    echo ✅ Backup der docker-compose.yml erstellt: docker-compose.yml.bak
    
    REM Prüfen, ob die Volumes-Einträge bereits vorhanden sind
    findstr /C:"./postgres-config/postgresql.conf:/etc/postgresql/postgresql.conf" docker-compose.yml > nul
    if %ERRORLEVEL% EQU 0 (
        echo ℹ️ Die PostgreSQL-Konfiguration ist bereits in docker-compose.yml eingebunden.
    ) else (
        echo ⚠️ Die docker-compose.yml muss manuell angepasst werden.
        echo.
        echo Bitte füge folgende Zeilen zum db-Service in der docker-compose.yml hinzu:
        echo.
        echo volumes:
        echo   - postgres_data:/var/lib/postgresql/data
        echo   - ./postgres-config/postgresql.conf:/etc/postgresql/postgresql.conf
        echo   - ./postgres-config/pg_hba.conf:/etc/postgresql/pg_hba.conf
        echo.
        echo command: postgres -c config_file=/etc/postgresql/postgresql.conf
    )
) else (
    echo ⚠️ WARNUNG: docker-compose.yml nicht gefunden. Bitte füge die Konfiguration manuell hinzu.
)

REM Lokale IP-Adresse abrufen
FOR /F "tokens=*" %%i IN ('ipconfig ^| findstr /C:"IPv4" ^| findstr /V "169.254"') DO SET IP_LINE=%%i
FOR /F "tokens=2 delims=:" %%i IN ("%IP_LINE%") DO SET LOCAL_IP=%%i
SET LOCAL_IP=%LOCAL_IP:~1%

echo.
echo ===== Konfiguration abgeschlossen =====
echo.
echo Führe folgende Schritte aus, um die Änderungen zu übernehmen:
echo 1. Starte die Docker-Container neu: docker-compose down ^&^& docker-compose up -d
echo 2. Prüfe, ob PostgreSQL korrekt läuft: docker-compose logs db
echo.
echo Verbindungsinformationen für dein Team:
echo - Host: %LOCAL_IP% (lokale IP) oder deine öffentliche IP
echo - Port: 5432
echo - Datenbank: cervelingua
echo - Benutzer: postgres
echo - Passwort: macintosh
echo.
echo WICHTIG: Aus Sicherheitsgründen solltest du das Passwort ändern, bevor du die Datenbank teilst.
echo Weitere Informationen findest du in der Dokumentation: docs\postgresql_sharing_guide_windows.md

pause