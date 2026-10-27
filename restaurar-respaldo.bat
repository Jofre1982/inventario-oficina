@echo off
rem ==========================================================================
rem  Devuelve la base de datos a como estaba en una copia de seguridad.
rem  Antes de restaurar guarda una copia del estado actual por si acaso.
rem ==========================================================================
setlocal EnableDelayedExpansion
title Restaurar copia de seguridad - Inventario Oficina
cd /d "%~dp0"
set "XAMPP=C:\xampp"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "MYSQLDUMP=%XAMPP%\mysql\bin\mysqldump.exe"

if not exist "respaldos\oficina_*.sql" (
    echo No hay copias de seguridad en la carpeta respaldos.
    pause
    exit /b 1
)

call "%~dp0windows\arrancar.bat" solo-mysql
if errorlevel 1 (
    echo [ERROR] No se pudo encender MySQL.
    pause
    exit /b 1
)
cd /d "%~dp0"

echo Copias disponibles (la mas reciente arriba):
echo.
set /a N=0
for /f "delims=" %%f in ('dir /b /o-n "respaldos\oficina_*.sql"') do (
    set /a N+=1
    set "ARCHIVO!N!=%%f"
    echo   !N!. %%f
)
echo.
set "OPCION="
set /p "OPCION=Escriba el numero de la copia que quiere restaurar (Enter = cancelar): "
if "%OPCION%"=="" goto cancelado
if not defined ARCHIVO%OPCION% (
    echo Numero no valido.
    goto cancelado
)
set "ELEGIDO=!ARCHIVO%OPCION%!"

echo.
echo ATENCION: todo lo registrado despues de esa copia se perdera.
choice /c SN /n /m "Restaurar %ELEGIDO%? [S/N] "
if errorlevel 2 goto cancelado

for /f %%d in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HHmm"') do set "AHORA=%%d"
echo Guardando el estado actual en respaldos\antes_de_restaurar_%AHORA%.sql ...
"%MYSQLDUMP%" -u root --single-transaction --routines --databases inventario_oficina > "respaldos\antes_de_restaurar_%AHORA%.sql"

echo Restaurando...
"%MYSQL%" -u root < "respaldos\%ELEGIDO%"
if errorlevel 1 (
    echo [ERROR] No se pudo restaurar. El estado anterior quedo guardado en respaldos\antes_de_restaurar_%AHORA%.sql
    pause
    exit /b 1
)
echo.
echo Listo. La base de datos quedo como estaba en %ELEGIDO%.
pause
exit /b 0

:cancelado
echo No se hizo ningun cambio.
pause
exit /b 0
