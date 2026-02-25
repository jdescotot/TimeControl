@echo off
echo ===============================================
echo   Verificador de Configuracion SMTP
echo ===============================================
echo.

cd /d "%~dp0"

echo [1/3] Verificando archivos de configuracion...
echo.

if exist "mail_config.php" (
    echo [OK] mail_config.php encontrado
) else (
    echo [ERROR] mail_config.php NO encontrado
)

if exist "mail\config.php" (
    echo [OK] mail\config.php encontrado
) else (
    echo [ERROR] mail\config.php NO encontrado
)

echo.
echo [2/3] Extrayendo contraseñas SMTP...
echo.

findstr /C:"'pass'" mail_config.php 2>nul
findstr /C:"'pass'" mail\config.php 2>nul

echo.
echo [3/3] Verificacion completa
echo.
echo ===============================================
echo   IMPORTANTE:
echo   - Ambos archivos deben tener la MISMA contraseña
echo   - La contraseña actual "Macarena.1710" es INCORRECTA
echo   - Necesitas la contraseña correcta de IONOS
echo ===============================================
echo.
echo Lee INSTRUCCIONES_CONTRASEÑA.md para mas ayuda
echo.
pause
