#!/bin/bash
# build-linux.sh - install ASD globally on Linux/WSL
# by Bandika

SRC_DIR="Src/Main"
INSTALL_DIR="/usr/local/lib/asd"
WRAPPER="/usr/local/bin/asd"

# --- STEP 1: Change to source directory ---
echo ">>> Changing to $SRC_DIR folder..."
cd "$SRC_DIR" || { echo "ERROR: $SRC_DIR folder not found!"; exit 1; }
echo "[OK]"

# --- STEP 2: Determine main ASD file ---
# The main file should be named 'asd' or 'asd.php'
MAIN_FILE=""
if [[ -f "asd.php" ]]; then
    MAIN_FILE="asd.php"
elif [[ -f "asd" ]]; then
    MAIN_FILE="asd"
else
    echo "ERROR: Main ASD file not found in $SRC_DIR (asd or asd.php)"
    exit 1
fi
echo ">>> Main ASD file detected: $MAIN_FILE"

# --- STEP 3: Make all PHP files executable ---
echo ">>> Making all PHP files executable..."
chmod +x *.php
echo "[OK]"

# --- STEP 4: Copy source to installation directory ---
echo ">>> Copying $SRC_DIR to $INSTALL_DIR ..."
sudo mkdir -p "$INSTALL_DIR"
sudo cp -r ./* "$INSTALL_DIR"
echo "[OK]"

# --- STEP 5: Create wrapper executable in /usr/local/bin ---
echo ">>> Creating global 'asd' command..."
sudo tee "$WRAPPER" > /dev/null <<EOF
#!/usr/bin/env bash
# Wrapper to run ASD from anywhere
exec php $INSTALL_DIR/$MAIN_FILE "\$@"
EOF

sudo chmod +x "$WRAPPER"
echo "[OK]"

echo ">>> ASD installed globally!"
echo "Thanks for using ASD (a simple dsl)"
echo "For more updates"
echo "go to: github.com/Bandikaaking/a_simple_dsl"
echo "You can now run it with:"
echo "  asd <filename.asd>"
