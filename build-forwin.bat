@echo off
REM build-windows.bat - install ASD globally on Windows
REM by Bandika
REM Must run as Administrator to write to C:\Windows

:: Set console colors
color 0F

:: Check for admin privileges
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: You must run this script as Administrator!
    echo Right-click and select "Run as administrator"
    pause
    exit /b 1
)

set SRC_DIR=Src\Main
set INSTALL_DIR=C:\Windows\asd
set WRAPPER_DIR=C:\Windows

echo.
echo === ASD Installer for Windows ===
echo.

echo ^>^>^> Changing to %SRC_DIR% folder...
if not exist "%SRC_DIR%" (
    echo ERROR: %SRC_DIR% folder not found!
    echo Please run this script from the ASD project root directory
    pause
    exit /b 1
)

cd /d "%SRC_DIR%"
if errorlevel 1 (
    echo ERROR: Cannot change to %SRC_DIR% directory
    pause
    exit /b 1
)
echo [OK]

echo.
echo ^>^>^> Determining main ASD file...
set MAIN_FILE=
if exist asd.php (
    set MAIN_FILE=asd.php
    echo [OK] Found asd.php
) else if exist asd (
    set MAIN_FILE=asd
    echo [OK] Found asd
) else (
    echo ERROR: Main ASD file not found in %SRC_DIR%
    echo Expected either 'asd' or 'asd.php'
    pause
    exit /b 1
)
echo ^>^>^> Main ASD file detected: %MAIN_FILE%

echo.
echo ^>^>^> Creating installation directory...
if not exist "%INSTALL_DIR%" (
    mkdir "%INSTALL_DIR%"
    if errorlevel 1 (
        echo ERROR: Failed to create installation directory
        pause
        exit /b 1
    )
)
echo [OK]

echo.
echo ^>^>^> Copying source files to %INSTALL_DIR% ...
xcopy * "%INSTALL_DIR%\" /E /Y /Q >nul
if errorlevel 1 (
    echo ERROR: Failed to copy files to %INSTALL_DIR%
    pause
    exit /b 1
)
echo [OK]

echo.
echo ^>^>^> Creating wrapper script...

:: Create batch wrapper
set WRAPPER_FILE=%WRAPPER_DIR%\asd.cmd

(
echo @echo off
echo REM ASD Wrapper Script
echo REM Installation: %INSTALL_DIR%
echo.
echo php "%INSTALL_DIR%\%MAIN_FILE%" %%*
) > "%TEMP%\asd.cmd"

copy /Y "%TEMP%\asd.cmd" "%WRAPPER_FILE%" >nul
if errorlevel 1 (
    echo ERROR: Failed to create wrapper script
    pause
    exit /b 1
)
del "%TEMP%\asd.cmd" 2>nul
echo [OK]

echo.
echo ^>^>^> Verifying PHP installation...
where php >nul 2>&1
if errorlevel 1 (
    echo WARNING: PHP not found in PATH!
    echo Make sure PHP is installed and added to your system PATH
    echo You can still run ASD using: php %INSTALL_DIR%\%MAIN_FILE%
) else (
    echo [OK] PHP found
)

echo.
echo ^>^>^> Testing ASD installation...
if exist "%WRAPPER_FILE%" (
    echo [OK] ASD command is available
) else (
    echo WARNING: ASD command not found
)

echo.
echo === Installation Complete ===
echo.
echo ^>^>^> ASD installed globally!
echo Thanks for using ASD (A Small DSL)
echo.
echo Installation details:
echo   • Source files: %INSTALL_DIR%
echo   • Main executable: %WRAPPER_FILE%
echo   • Main file: %MAIN_FILE%
echo.
echo For more updates:
echo   github.com/Bandikaaking/a_simple_dsl
echo.
echo You can now run it with:
echo   asd filename.asd
echo.
echo Example:
echo   echo PRINT Hello World ^> test.asd
echo   asd test.asd
echo.
echo ^>^>^> Creating uninstall script...

:: Create uninstaller
set UNINSTALLER=%INSTALL_DIR%\uninstall.bat

(
echo @echo off
echo REM ASD Uninstaller
echo.
echo echo Uninstalling ASD...
echo.
echo :: Remove installation directory
echo rmdir /S /Q "%INSTALL_DIR%" 2^>nul
echo.
echo :: Remove wrapper
echo del /F /Q "%WRAPPER_FILE%" 2^>nul
echo.
echo echo ASD has been uninstalled
echo pause
) > "%UNINSTALLER%"

echo [OK]
echo Uninstall script created: %UNINSTALLER%
echo Run "%UNINSTALLER%" to remove ASD
echo.
pause