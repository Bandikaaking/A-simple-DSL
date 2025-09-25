@echo off
REM build-windows.bat - install ASD globally on Windows
REM by Bandika
REM Must run as Administrator to write to C:\Windows
:: Check for admin privileges
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: You must run this script as Administrator!
    pause
    exit /b 1
)

set SRC_DIR=Src\Main
set INSTALL_DIR=C:\Windows

echo >>> Changing to %SRC_DIR% folder...
cd /d "%SRC_DIR%"
if errorlevel 1 (
    echo ERROR: %SRC_DIR% folder not found!
    exit /b 1
)
echo [OK]

echo >>> Determining main ASD file...
set MAIN_FILE=
if exist asd.php (
    set MAIN_FILE=asd.php
) else if exist asd (
    set MAIN_FILE=asd
) else (
    echo ERROR: Main ASD file not found in %SRC_DIR% (asd or asd.php)
    exit /b 1
)
echo >>> Main ASD file detected: %MAIN_FILE%

echo >>> Copying %SRC_DIR% contents to %INSTALL_DIR% ...
xcopy * "%INSTALL_DIR%\" /E /Y >nul
if errorlevel 1 (
    echo ERROR: Failed to copy files to %INSTALL_DIR%
    exit /b 1
)
echo [OK]

echo >>> ASD installed globally in C:\Windows!
echo Thanks for using ASD (a simple DSL)
echo For more updates:
echo   github.com/Bandikaaking/a_simple_dsl
echo You can now run it with:
echo   php %MAIN_FILE% filename.asd
pause
