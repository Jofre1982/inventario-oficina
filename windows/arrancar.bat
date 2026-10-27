@echo off
rem ==========================================================================
rem  Enciende la base de datos (MySQL de XAMPP) y el servidor del sistema,
rem  ambos ocultos, y hace la copia de seguridad del dia.
rem  Lo usa el acceso directo del escritorio (InventarioOficina.vbs).
rem  Uso: arrancar.bat              -> todo
rem       arrancar.bat solo-mysql   -> solo la base de datos (lo usa el instalador)
rem  Codigos de salida: 0 ok, 10 sin XAMPP, 11 sin instalar, 20 MySQL, 30 servidor
rem
rem  Usa el puerto 8010 para poder convivir con Inventario General (puerto 8000)
rem  en el mismo computador.
rem ==========================================================================
setlocal EnableDelayedExpansion
cd /d "%~dp0.."

set "XAMPP=C:\xampp"
set "PHP=%XAMPP%\php\php.exe"
set "MYSQLD=%XAMPP%\mysql\bin\mysqld.exe"
set "MYSQLADMIN=%XAMPP%\mysql\bin\mysqladmin.exe"
set "MYSQLDUMP=%XAMPP%\mysql\bin\mysqldump.exe"
set "PORT=8010"

if not exist "%PHP%" exit /b 10
if not exist "%MYSQLD%" exit /b 10

rem ---------- 1. Base de datos ----------
"%MYSQLADMIN%" -u root ping >nul 2>&1
if errorlevel 1 (
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%MYSQLD%' -ArgumentList '--defaults-file=%XAMPP%\mysql\bin\my.ini','--standalone' -WorkingDirectory '%XAMPP%\mysql' -WindowStyle Hidden"
    for /l %%i in (1,1,40) do (
        "%MYSQLADMIN%" -u root ping >nul 2>&1 && goto mysql_ok
        ping -n 2 127.0.0.1 >nul
    )
    exit /b 20
)
:mysql_ok
if /i "%~1"=="solo-mysql" exit /b 0

if not exist "vendor\autoload.php" exit /b 11
if not exist ".env" exit /b 11

rem ---------- 2. Servidor del sistema ----------
netstat -an | findstr /r /c:"127\.0\.0\.1:%PORT% .*LISTENING" >nul
if errorlevel 1 (
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%PHP%' -ArgumentList '-S','127.0.0.1:%PORT%','\"%CD%\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php\"' -WorkingDirectory '%CD%\public' -WindowStyle Hidden"
    for /l %%i in (1,1,20) do (
        netstat -an | findstr /r /c:"127\.0\.0\.1:%PORT% .*LISTENING" >nul && goto web_ok
        ping -n 2 127.0.0.1 >nul
    )
    exit /b 30
)
:web_ok

rem ---------- 3. Copia de seguridad del dia (se guardan las ultimas 30) ----------
set "DB=inventario_oficina"
set "DBPASS="
for /f "tokens=1,* delims==" %%a in ('findstr /b /c:"DB_DATABASE=" /c:"DB_PASSWORD=" ".env"') do (
    if /i "%%a"=="DB_DATABASE" set "DB=%%b"
    if /i "%%a"=="DB_PASSWORD" set "DBPASS=%%b"
)
for /f %%d in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd"') do set "HOY=%%d"
if not exist "respaldos" mkdir "respaldos"
if not exist "respaldos\oficina_%HOY%.sql" (
    if defined DBPASS (set "PASSARG=--password=!DBPASS!") else (set "PASSARG=")
    "%MYSQLDUMP%" -u root !PASSARG! --single-transaction --routines --databases "%DB%" > "respaldos\oficina_%HOY%.sql" 2>nul
    if errorlevel 1 del "respaldos\oficina_%HOY%.sql" >nul 2>&1
    powershell -NoProfile -Command "Get-ChildItem 'respaldos\oficina_*.sql' | Sort-Object Name -Descending | Select-Object -Skip 30 | Remove-Item -ErrorAction SilentlyContinue"
)

exit /b 0
