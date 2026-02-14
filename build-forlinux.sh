#!/bin/bash
# build-linux.sh - install ASD globally on Linux/WSL
# by Bandika

SRC_DIR="Src/Main"
INSTALL_DIR="/usr/local/lib/asd"
WRAPPER="/usr/local/bin/asd"

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# --- STEP 1: Change to source directory ---
echo -e "${YELLOW}>>>${NC} Changing to $SRC_DIR folder..."
if [ ! -d "$SRC_DIR" ]; then
    echo -e "${RED}ERROR:${NC} $SRC_DIR folder not found!"
    echo "Please run this script from the ASD project root directory"
    exit 1
fi

cd "$SRC_DIR" || { 
    echo -e "${RED}ERROR:${NC} Cannot change to $SRC_DIR directory"
    exit 1
}
echo -e "${GREEN}[OK]${NC}"

# --- STEP 2: Determine main ASD file ---
echo -e "${YELLOW}>>>${NC} Looking for main ASD file..."
MAIN_FILE=""
if [[ -f "asd.php" ]]; then
    MAIN_FILE="asd.php"
    echo -e "${GREEN}[OK]${NC} Found asd.php"
elif [[ -f "asd" ]]; then
    MAIN_FILE="asd"
    echo -e "${GREEN}[OK]${NC} Found asd"
else
    echo -e "${RED}ERROR:${NC} Main ASD file not found in $SRC_DIR"
    echo "Expected either 'asd' or 'asd.php'"
    exit 1
fi
echo ">>> Main ASD file detected: $MAIN_FILE"

# --- STEP 3: Make all PHP files executable ---
echo -e "${YELLOW}>>>${NC} Making all PHP files executable..."
chmod +x *.php 2>/dev/null || true
chmod +x asd 2>/dev/null || true
echo -e "${GREEN}[OK]${NC}"

# --- STEP 4: Copy source to installation directory ---
echo -e "${YELLOW}>>>${NC} Copying source to $INSTALL_DIR ..."

# Check if we have sudo access
if ! sudo -v &>/dev/null; then
    echo -e "${RED}ERROR:${NC} This installation requires sudo privileges"
    exit 1
fi

# Create installation directory and copy files
sudo mkdir -p "$INSTALL_DIR"
if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR:${NC} Failed to create installation directory"
    exit 1
fi

sudo cp -r ./* "$INSTALL_DIR"
if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR:${NC} Failed to copy files to installation directory"
    exit 1
fi
echo -e "${GREEN}[OK]${NC}"

# --- STEP 5: Create wrapper executable in /usr/local/bin ---
echo -e "${YELLOW}>>>${NC} Creating global 'asd' command..."

# Create temporary wrapper file
TEMP_WRAPPER=$(mktemp)
cat > "$TEMP_WRAPPER" <<EOF
#!/usr/bin/env bash
# ASD - A Small DSL
# Wrapper to run ASD from anywhere
# Installation: $INSTALL_DIR

PHP_BIN=\$(command -v php)
if [ -z "\$PHP_BIN" ]; then
    echo "ERROR: PHP is not installed or not in PATH"
    echo "Please install PHP and try again"
    exit 1
fi

exec "\$PHP_BIN" "$INSTALL_DIR/$MAIN_FILE" "\$@"
EOF

# Install the wrapper
sudo mv "$TEMP_WRAPPER" "$WRAPPER"
sudo chmod +x "$WRAPPER"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}[OK]${NC}"
else
    echo -e "${RED}ERROR:${NC} Failed to create wrapper script"
    exit 1
fi

# --- STEP 6: Verify installation ---
echo -e "${YELLOW}>>>${NC} Verifying installation..."
if command -v asd &>/dev/null; then
    echo -e "${GREEN}[OK]${NC} ASD command is available"
else
    echo -e "${RED}WARNING:${NC} ASD command not found in PATH"
    echo "You may need to add /usr/local/bin to your PATH"
fi

# --- STEP 7: Show success message ---
echo ""
echo -e "${GREEN}>>> ASD installed globally!${NC}"
echo "Thanks for using ASD (A Small DSL)"
echo ""
echo -e "${YELLOW}Installation details:${NC}"
echo "  • Source files: $INSTALL_DIR"
echo "  • Main executable: $WRAPPER"
echo "  • Main file: $MAIN_FILE"
echo ""
echo -e "${YELLOW}For more updates:${NC}"
echo "  • GitHub: github.com/Bandikaaking/a_simple_dsl"
echo ""
echo -e "${YELLOW}You can now run it with:${NC}"
echo "  asd <filename.asd>"
echo ""
echo -e "${YELLOW}Example:${NC}"
echo "  echo 'PRINT Hello World' > test.asd"
echo "  asd test.asd"
echo ""

# --- Optional: Create uninstall script ---
UNINSTALLER="$INSTALL_DIR/uninstall.sh"
sudo tee "$UNINSTALLER" > /dev/null <<EOF
#!/bin/bash
# Uninstall ASD
echo "Uninstalling ASD..."
sudo rm -rf "$INSTALL_DIR"
sudo rm -f "$WRAPPER"
echo "ASD has been uninstalled"
EOF

sudo chmod +x "$UNINSTALLER"
echo -e "${YELLOW}Uninstall script created:${NC} $UNINSTALLER"
echo "Run 'sudo $UNINSTALLER' to remove ASD"