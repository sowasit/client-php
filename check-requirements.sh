#!/bin/bash

# check-requirements.sh
# Requirements checker for sowasit/client-php
# This script checks if PHP and required extensions are properly installed
# Now with accurate version mismatch detection!

# Colors for better readability
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔍 Checking requirements for sowasit/client-php${NC}\n"

#=====================================
# DETECT ALL PHP VERSIONS INSTALLED
#=====================================

echo -e "${CYAN}📋 Detecting PHP installations...${NC}"

# Find all PHP binaries in common locations and remove duplicates
PHP_BINARIES=$(find /usr/bin -name 'php*' -type f -executable 2>/dev/null | grep -E 'php[0-9]+\.[0-9]+$' | sort -u -r)
PHP_VERSIONS=()
PHP_VERSIONS_LIST=()

if [ -n "$PHP_BINARIES" ]; then
    echo -e "Found PHP versions:"
    for php_bin in $PHP_BINARIES; do
        version=$($php_bin -v 2>/dev/null | head -n1 | cut -d' ' -f2)
        # Extract major.minor version (e.g., 8.3 from 8.3.30)
        major_minor=$(echo $version | cut -d '.' -f 1-2)
        
        # Check if we already have this version
        if [[ ! " ${PHP_VERSIONS_LIST[@]} " =~ " ${major_minor} " ]]; then
            echo -e "  - ${CYAN}$php_bin${NC} (version ${GREEN}$version${NC})"
            PHP_VERSIONS+=("$php_bin")
            PHP_VERSIONS_LIST+=("$major_minor")
        else
            echo -e "  - ${CYAN}$php_bin${NC} (version ${GREEN}$version${NC}) ${YELLOW}(duplicate, skipping)${NC}"
        fi
    done
else
    # Fallback to just the default php command
    PHP_VERSIONS=("php")
fi

echo ""

#=====================================
# CHECK DEFAULT PHP VERSION
#=====================================

# Get default PHP version (the one from PATH)
if command -v php &> /dev/null; then
    DEFAULT_PHP=$(which php)
    DEFAULT_VERSION=$(php -v 2>/dev/null | head -n 1 | cut -d ' ' -f 2)
    echo -e "${BLUE}👉 Default PHP:${NC} ${CYAN}$DEFAULT_PHP${NC} (version ${GREEN}$DEFAULT_VERSION${NC})"
    
    # Extract major.minor version (e.g., 8.3 from 8.3.30)
    DEFAULT_MAJOR_MINOR=$(echo $DEFAULT_VERSION | cut -d '.' -f 1-2)
else
    echo -e "${RED}❌ No default PHP found in PATH${NC}"
    exit 1
fi

echo ""

#=====================================
# PHP INSTALLATION CHECK
#=====================================

echo -e "${BLUE}📊 PHP Version Check${NC}"

# Check if PHP version is sufficient (>= 7.4)
PHP_VERSION_NUM=$(echo $DEFAULT_VERSION | cut -d '.' -f 1-2)
if [[ $(echo "$PHP_VERSION_NUM < 7.4" | bc) -eq 1 ]]; then
    echo -e "${RED}❌ PHP version $DEFAULT_VERSION is too old. PHP 7.4 or higher is required.${NC}"
    echo -e "${YELLOW}Please upgrade PHP:${NC}"
    echo "  - Debian/Ubuntu with ondrej/php PPA:"
    echo "    sudo add-apt-repository ppa:ondrej/php"
    echo "    sudo apt update"
    echo "    sudo apt install php8.3 php8.3-cli php8.3-common"
    exit 1
else
    echo -e "${GREEN}✅ PHP version $DEFAULT_VERSION is sufficient${NC}"
fi

echo ""

#=====================================
# CHECK WHICH EXTENSIONS ARE AVAILABLE SYSTEM-WIDE
#=====================================

echo -e "${BLUE}🔎 Checking which PHP extensions are installed system-wide${NC}"

# Check what extension packages are installed (Debian/Ubuntu specific)
EXTENSION_MISMATCH=false

if [ -f /etc/debian_version ]; then
    # For Debian/Ubuntu, check installed PHP extension packages
    INSTALLED_EXT_PACKAGES=$(dpkg -l 2>/dev/null | grep -E '^ii.*php[0-9]+\.[0-9]+-(intl|curl|mbstring)' | awk '{print $2}')
    
    if [ -n "$INSTALLED_EXT_PACKAGES" ]; then
        echo -e "Installed PHP extension packages:"
        for pkg in $INSTALLED_EXT_PACKAGES; do
            # Extract which PHP version this package is for
            PKG_VERSION=$(echo $pkg | grep -oE 'php[0-9]+\.[0-9]+' | sed 's/php//')
            echo -e "  - ${CYAN}$pkg${NC} (for PHP ${GREEN}$PKG_VERSION${NC})"
            
            # Check if this package matches the default PHP version
            if [ "$PKG_VERSION" != "$DEFAULT_MAJOR_MINOR" ]; then
                EXTENSION_MISMATCH=true
            fi
        done
    else
        echo -e "${YELLOW}No specific PHP extension packages found${NC}"
    fi
fi

#=====================================
# PHP EXTENSIONS CHECK (for default PHP)
#=====================================

echo -e "\n${BLUE}📦 Checking PHP Extensions for default PHP ($DEFAULT_PHP)${NC}"

# Define required extensions
declare -A REQUIREMENTS
REQUIREMENTS=(
    ["intl"]="Extension intl (provides Normalizer class)"
    ["curl"]="Extension cURL (for HTTP requests)"
    ["mbstring"]="Extension mbstring (multibyte string support)"
    ["json"]="Extension JSON (usually built-in)"
)

# Counter for missing extensions
MISSING_COUNT=0
MISSING_EXTENSIONS=""

# Check each extension using the default PHP binary
for ext in "${!REQUIREMENTS[@]}"; do
    if $DEFAULT_PHP -m 2>/dev/null | grep -q "^$ext$"; then
        echo -e "${GREEN}✅ ${REQUIREMENTS[$ext]}: OK${NC}"
    else
        echo -e "${RED}❌ ${REQUIREMENTS[$ext]}: MISSING${NC}"
        MISSING_COUNT=$((MISSING_COUNT + 1))
        MISSING_EXTENSIONS="$MISSING_EXTENSIONS $ext"
    fi
done

echo ""

#=====================================
# COMPOSER CHECK
#=====================================

echo -e "${BLUE}📦 Checking Composer${NC}"

# Check if Composer is installed
HAS_COMPOSER=false
HAS_COMPOSER_PHAR=false

if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version 2>/dev/null | head -n 1)
    echo -e "${GREEN}✅ Composer is installed: $COMPOSER_VERSION${NC}"
    HAS_COMPOSER=true
else
    echo -e "${YELLOW}⚠️  Composer is not installed in PATH${NC}"
fi

# Check if Composer PHAR exists in current directory or parent
if [ -f "composer.phar" ]; then
    echo -e "${GREEN}✅ composer.phar found in current directory${NC}"
    HAS_COMPOSER_PHAR=true
elif [ -f "../composer.phar" ]; then
    echo -e "${GREEN}✅ composer.phar found in parent directory${NC}"
    HAS_COMPOSER_PHAR=true
    # Create a symlink or note it
    COMPOSER_PHAR_PATH="../composer.phar"
else
    echo -e "${YELLOW}⚠️  composer.phar not found${NC}"
fi

echo ""

#=====================================
# RESULTS AND RECOMMENDATIONS
#=====================================

if [ $MISSING_COUNT -eq 0 ]; then
    echo -e "${GREEN}🎉 All requirements are satisfied!${NC}"
    echo -e "${GREEN}You can now install sowasit/client-php${NC}"
    
    # Suggest installation command based on available Composer
    if [ "$HAS_COMPOSER" = true ]; then
        echo -e "\n${BLUE}Run:${NC} composer require sowasit/client-php"
    elif [ "$HAS_COMPOSER_PHAR" = true ]; then
        if [ -n "$COMPOSER_PHAR_PATH" ]; then
            echo -e "\n${BLUE}Run:${NC} php $COMPOSER_PHAR_PATH require sowasit/client-php"
        else
            echo -e "\n${BLUE}Run:${NC} php composer.phar require sowasit/client-php"
        fi
    else
        echo -e "\n${YELLOW}First, install Composer:${NC}"
        echo "  curl -sS https://getcomposer.org/installer | php"
        echo "  sudo mv composer.phar /usr/local/bin/composer"
        echo -e "\n${BLUE}Then run:${NC} composer require sowasit/client-php"
    fi
else
    echo -e "${RED}❌ $MISSING_COUNT extension(s) are missing for PHP $DEFAULT_VERSION${NC}"
    echo -e "${YELLOW}Please install missing extensions:${NC}\n"
    
    # Installation instructions based on OS detection
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Linux - try to detect distribution
        if [ -f /etc/debian_version ]; then
            echo "📦 Debian/Ubuntu:"
            
            if [ "$EXTENSION_MISMATCH" = true ]; then
                echo -e "${YELLOW}⚠️  You have extensions installed for a different PHP version!${NC}"
                echo "    Your default PHP is version ${GREEN}$DEFAULT_MAJOR_MINOR${NC}"
                echo "    But you have extensions installed for other versions."
                echo ""
                echo "    Install extensions for your specific PHP version:"
            else
                echo "    Install the required extensions:"
            fi
            
            echo ""
            echo -e "${BLUE}  sudo apt update"
            echo "  sudo apt install php$DEFAULT_MAJOR_MINOR-intl php$DEFAULT_MAJOR_MINOR-curl php$DEFAULT_MAJOR_MINOR-mbstring${NC}"
            echo ""
            echo "    (Note: Using 'php$DEFAULT_MAJOR_MINOR-...' ensures you get extensions for YOUR PHP version)"
            
        elif [ -f /etc/fedora-release ] || [ -f /etc/redhat-release ]; then
            echo "📦 Fedora/RHEL:"
            echo "  sudo dnf install php-intl php-curl php-mbstring"
            echo ""
            echo "  For PHP $DEFAULT_MAJOR_MINOR, you might need:"
            echo "  sudo dnf install php$DEFAULT_MAJOR_MINOR-intl php$DEFAULT_MAJOR_MINOR-curl php$DEFAULT_MAJOR_MINOR-mbstring"
        else
            echo "📦 Linux:"
            echo "  Please install: php-intl, php-curl, php-mbstring"
            echo "  For PHP $DEFAULT_MAJOR_MINOR, try: php$DEFAULT_MAJOR_MINOR-intl php$DEFAULT_MAJOR_MINOR-curl php$DEFAULT_MAJOR_MINOR-mbstring"
        fi
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        echo "📦 macOS:"
        echo "  brew install php-intl php-curl php-mbstring"
        echo "  # or with pecl:"
        echo "  pecl install intl"
    else
        echo "📦 Please install: intl, curl, mbstring PHP extensions"
    fi
    
    echo ""
    echo -e "${YELLOW}After installation, verify with:${NC} php -m | grep -E \"intl|curl|mbstring\""
    echo -e "${YELLOW}Then run this script again${NC}"
    exit 1
fi

#=====================================
# COMPOSER TROUBLESHOOTING
#=====================================

echo -e "\n${BLUE}⚠️  If you encounter 'Class \"Normalizer\" not found' error:${NC}"
echo "  1. Verify intl extension is enabled: php -m | grep intl"
echo "  2. Check your default PHP version: php -v"
echo "  3. Install intl for your specific PHP version:"
echo "     sudo apt install php$DEFAULT_MAJOR_MINOR-intl"
echo "  4. Or use Composer PHAR directly:"
echo "     curl -sS https://getcomposer.org/installer | php"
echo "     php composer.phar require sowasit/client-php"

exit 0