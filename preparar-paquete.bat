@echo off
rem ==========================================================================
rem  Arma un .zip listo para llevar a otra oficina en una USB.
rem  Ejecutelo en SU computador (donde el sistema ya funciona).
rem  El paquete incluye las librerias, asi que en el destino no hace falta internet.
rem  NO incluye su base de datos, su .env, sus fotos ni sus respaldos.
rem ==========================================================================
setlocal
cd /d "%~dp0"
set "PHP=C:\xampp\php\php.exe"
set "TMPDIR=%TEMP%\inventario_oficina_paquete"
set "DESTINO=%TMPDIR%\InventarioOficina"
for /f "delims=" %%d in ('powershell -NoProfile -Command "[Environment]::GetFolderPath(0)"') do set "ESCRITORIO=%%d"
set "ZIP=%ESCRITORIO%\InventarioOficina-instalador.zip"

if not exist "vendor\autoload.php" (
    echo Primero instale el sistema en este computador con instalar.bat
    pause
    exit /b 1
)

echo Preparando librerias de produccion...
"%PHP%" composer.phar install --no-dev --optimize-autoloader --no-interaction
if errorlevel 1 (
    echo [ERROR] No se pudieron preparar las librerias.
    pause
    exit /b 1
)

echo Copiando archivos...
if exist "%TMPDIR%" rmdir /s /q "%TMPDIR%"
robocopy "%~dp0." "%DESTINO%" /E /NFL /NDL /NJH /NJS /NP ^
    /XD .git .github node_modules tests respaldos "%~dp0public\image" "%~dp0storage\logs" "%~dp0storage\framework\cache\data" "%~dp0storage\framework\sessions" "%~dp0storage\framework\views" ^
    /XF .env .env.backup .phpunit.result.cache database.sqlite *.log preparar-paquete.bat >nul
mkdir "%DESTINO%\public\image\items" >nul 2>&1
if not exist "%DESTINO%\storage\logs" mkdir "%DESTINO%\storage\logs"
if not exist "%DESTINO%\storage\framework\cache\data" mkdir "%DESTINO%\storage\framework\cache\data"
if not exist "%DESTINO%\storage\framework\sessions" mkdir "%DESTINO%\storage\framework\sessions"
if not exist "%DESTINO%\storage\framework\views" mkdir "%DESTINO%\storage\framework\views"

echo Comprimiendo...
if exist "%ZIP%" del "%ZIP%"
powershell -NoProfile -Command "Compress-Archive -Path '%DESTINO%' -DestinationPath '%ZIP%'"
rmdir /s /q "%TMPDIR%"

echo Restaurando las librerias de desarrollo en este computador...
"%PHP%" composer.phar install --no-interaction >nul 2>&1

echo.
echo ============================================================
echo   Paquete listo en su escritorio:
echo   InventarioOficina-instalador.zip
echo.
echo   En el otro computador:
echo   1. Instalar XAMPP en C:\xampp
echo   2. Descomprimir el .zip en C:\  (queda la carpeta C:\InventarioOficina)
echo   3. Doble clic en C:\InventarioOficina\instalar.bat
echo ============================================================
pause
