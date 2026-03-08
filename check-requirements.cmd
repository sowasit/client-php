@echo off
setlocal enabledelayedexpansion

:: check-requirements.cmd
:: Requirements checker for sowasit/client-php
:: This script checks if PHP and required extensions are properly installed on Windows

color 0F
echo ========================================
echo 🔍 Checking requirements for sowasit/client-php
echo ========================================
echo.

::=====================================
:: CHECK PHP INSTALLATION
::=====================================

echo 📋 Checking PHP installation...
echo.

:: Try to find PHP in PATH
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP is not installed or not in PATH
    echo.
    echo 📦 Please install PHP:
    echo   Option 1: Download from https://windows.php.net/download/
    echo   Option 2: Use XAMPP (https://www.apachefriends.org/)
    echo   Option 3: Use WampServer (https://www.wampserver.com/)
    echo.
    echo After installation, add PHP to your PATH:
    echo   setx PATH "%%PATH%%;C:\path\to\php"
    echo.
    pause
    exit /b 1
)

:: Get PHP version
for /f "tokens=2 delims= " %%a in ('php -v 2^>nul ^| find "PHP"') do (
    set PHP_VERSION=%%a
    goto :version_found
)
:version_found
if not defined PHP_VERSION (
    echo ❌ Could not determine PHP version
    pause
    exit /b 1
)

echo ✅ PHP found: %PHP_VERSION%
echo.

:: Extract major.minor version (e.g., 8.3 from 8.3.30)
for /f "tokens=1,2 delims=." %%a in ("%PHP_VERSION%") do (
    set PHP_MAJOR=%%a
    set PHP_MINOR=%%b
    set PHP_MAJOR_MINOR=%%a.%%b
)

:: Check if PHP version is sufficient (>= 7.4)
if %PHP_MAJOR% LSS 7 (
    echo ❌ PHP version %PHP_VERSION% is too old. PHP 7.4 or higher is required.
    echo.
    echo 📦 Please upgrade PHP:
    echo   Download latest from https://windows.php.net/download/
    pause
    exit /b 1
)
if %PHP_MAJOR% EQU 7 (
    if %PHP_MINOR% LSS 4 (
        echo ❌ PHP version %PHP_VERSION% is too old. PHP 7.4 or higher is required.
        echo.
        echo 📦 Please upgrade PHP:
        echo   Download latest from https://windows.php.net/download/
        pause
        exit /b 1
    )
)
echo ✅ PHP version requirement satisfied
echo.

::=====================================
:: GET PHP EXTENSION DIRECTORY
::=====================================

:: Try to find PHP extension directory
for /f "tokens=2 delims= " %%a in ('php -i 2^>nul ^| find /i "extension_dir" ^| find "=>"') do (
    set EXTENSION_DIR=%%a
    goto :ext_dir_found
)
:ext_dir_found
if not defined EXTENSION_DIR (
    echo ⚠️  Could not determine PHP extension directory
    set EXTENSION_DIR=
)

::=====================================
:: CHECK PHP EXTENSIONS
::=====================================

echo 📦 Checking PHP extensions...
echo.

:: Define required extensions
set MISSING_COUNT=0
set MISSING_EXTENSIONS=

:: Check intl extension
php -m 2>nul | find /i "intl" >nul
if !errorlevel! equ 0 (
    echo ✅ Extension intl (provides Normalizer class): OK
) else (
    echo ❌ Extension intl (provides Normalizer class): MISSING
    set /a MISSING_COUNT+=1
    set MISSING_EXTENSIONS=!MISSING_EXTENSIONS! intl
)

:: Check curl extension
php -m 2>nul | find /i "curl" >nul
if !errorlevel! equ 0 (
    echo ✅ Extension cURL (for HTTP requests): OK
) else (
    echo ❌ Extension cURL (for HTTP requests): MISSING
    set /a MISSING_COUNT+=1
    set MISSING_EXTENSIONS=!MISSING_EXTENSIONS! curl
)

:: Check mbstring extension
php -m 2>nul | find /i "mbstring" >nul
if !errorlevel! equ 0 (
    echo ✅ Extension mbstring (multibyte string support): OK
) else (
    echo ❌ Extension mbstring (multibyte string support): MISSING
    set /a MISSING_COUNT+=1
    set MISSING_EXTENSIONS=!MISSING_EXTENSIONS! mbstring
)

:: Check json extension (usually built-in)
php -m 2>nul | find /i "json" >nul
if !errorlevel! equ 0 (
    echo ✅ Extension JSON (usually built-in): OK
) else (
    echo ⚠️  Extension JSON: MISSING (but usually built-in)
)

echo.

::=====================================
:: CHECK PHP.INI
::=====================================

:: Find php.ini location
for /f "tokens=2 delims= " %%a in ('php -i 2^>nul ^| find /i "Loaded Configuration File" ^| find "=>"') do (
    set PHP_INI=%%a
    goto :ini_found
)
:ini_found
if defined PHP_INI (
    echo 📄 php.ini location: !PHP_INI!
) else (
    echo ⚠️  Could not find php.ini location
)

echo.

::=====================================
:: CHECK COMPOSER
::=====================================

echo 📦 Checking Composer...
echo.

:: Check if Composer is installed
set HAS_COMPOSER=false
where composer >nul 2>&1
if !errorlevel! equ 0 (
    for /f "tokens=*" %%a in ('composer --version 2^>nul') do set COMPOSER_VERSION=%%a
    echo ✅ Composer is installed: !COMPOSER_VERSION!
    set HAS_COMPOSER=true
) else (
    echo ⚠️  Composer is not installed in PATH
)

:: Check if composer.phar exists
if exist "composer.phar" (
    echo ✅ composer.phar found in current directory
    set HAS_COMPOSER_PHAR=true
) else if exist "..\composer.phar" (
    echo ✅ composer.phar found in parent directory
    set HAS_COMPOSER_PHAR=true
    set COMPOSER_PHAR_PATH=..\composer.phar
) else (
    echo ⚠️  composer.phar not found
    set HAS_COMPOSER_PHAR=false
)

echo.

::=====================================
:: WINDOWS-SPECIFIC PHP EXTENSION INFO
::=====================================

if %MISSING_COUNT% gtr 0 (
    echo 🔧 Windows PHP Extension Installation Guide:
    echo ============================================
    echo.
    echo 1. Locate your PHP installation folder
    echo    Usually: C:\php or C:\xampp\php or C:\wamp64\bin\php\php%PHP_MAJOR_MINOR%
    echo.
    echo 2. Find the 'ext' folder (e.g., C:\php\ext)
    echo.
    echo 3. Make sure these DLL files exist:
    echo    - php_intl.dll
    echo    - php_curl.dll  
    echo    - php_mbstring.dll
    echo.
    echo 4. Edit your php.ini file and uncomment/add these lines:
    echo    extension=intl
    echo    extension=curl
    echo    extension=mbstring
    echo.
    echo 5. If DLLs are missing, download them:
    echo    - They should come with PHP installation
    echo    - Or download from: https://windows.php.net/downloads/pecl/releases/
    echo.
    echo 6. Restart your web server if using one (Apache, IIS, etc.)
    echo.
)

::=====================================
:: RESULTS AND RECOMMENDATIONS
::=====================================

if %MISSING_COUNT% equ 0 (
    echo 🎉 All requirements are satisfied!
    echo You can now install sowasit/client-php
    echo.
    
    if "!HAS_COMPOSER!"=="true" (
        echo 📦 Run: composer require sowasit/client-php
    ) else if "!HAS_COMPOSER_PHAR!"=="true" (
        if defined COMPOSER_PHAR_PATH (
            echo 📦 Run: php !COMPOSER_PHAR_PATH! require sowasit/client-php
        ) else (
            echo 📦 Run: php composer.phar require sowasit/client-php
        )
    ) else (
        echo 📦 First, install Composer:
        echo   curl -sS https://getcomposer.org/installer | php
        echo.
        echo 📦 Then run: php composer.phar require sowasit/client-php
    )
) else (
    echo ❌ %MISSING_COUNT% extension(s) are missing for PHP %PHP_VERSION%
    echo.
    echo 🔧 Quick fix for Windows:
    echo =========================
    echo.
    echo Option A - If using XAMPP/WampServer:
    echo   Open the control panel and enable these extensions:
    echo   - intl
    echo   - curl
    echo   - mbstring
    echo.
    echo Option B - Manual configuration:
    echo   1. Open: !PHP_INI!
    echo   2. Find and uncomment these lines:
    echo      ;extension=intl      -^> extension=intl
    echo      ;extension=curl      -^> extension=curl
    echo      ;extension=mbstring  -^> extension=mbstring
    echo   3. Save the file
    echo   4. Restart your web server if applicable
    echo.
    echo Option C - If DLLs are missing:
    echo   Download from: https://windows.php.net/downloads/pecl/releases/
    echo   Copy DLLs to: !EXTENSION_DIR!
)

echo.
echo ========================================
echo ⚠️  If you encounter 'Class "Normalizer" not found' error:
echo ========================================
echo 1. Verify intl extension is enabled: php -m ^| find "intl"
echo 2. Check php.ini has: extension=intl
echo 3. Restart your web server
echo 4. Or use Composer PHAR directly:
echo    curl -sS https://getcomposer.org/installer ^| php
echo    php composer.phar require sowasit/client-php
echo.

if %MISSING_COUNT% gtr 0 (
    echo.
    echo Press any key to exit...
    pause >nul
) else (
    echo ✅ All good! Press any key to exit...
    pause >nul
)

exit /b 0