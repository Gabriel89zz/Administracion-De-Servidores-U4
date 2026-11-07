#!/bin/bash
# ==============================================================================
# Script para la creación automática de usuarios y estructura Maildir
# ==============================================================================
set -e

crear_usuario() {
    local USERNAME=$1
    local PASSWORD=$2

    if id "$USERNAME" &>/dev/null; then
        echo "[!] El usuario $USERNAME ya existe. Actualizando contraseña..."
    else
        echo "[*] Creando usuario $USERNAME..."
        sudo adduser --gecos "" --disabled-password "$USERNAME"
    fi

    echo "$USERNAME:$PASSWORD" | sudo chpasswd
    echo "[+] Contraseña establecida para $USERNAME."

    echo "[*] Inicializando directorios Maildir para $USERNAME..."
    local HOME_DIR="/home/$USERNAME"
    sudo mkdir -p "$HOME_DIR/Maildir/cur"
    sudo mkdir -p "$HOME_DIR/Maildir/new"
    sudo mkdir -p "$HOME_DIR/Maildir/tmp"
    sudo chmod -R 700 "$HOME_DIR/Maildir"
    sudo chown -R "$USERNAME:$USERNAME" "$HOME_DIR/Maildir"
    echo "[+] Estructura Maildir lista para $USERNAME."
}

echo "=== INICIALIZANDO CUENTAS DE CORREO CORPORATIVAS ==="
# Creación de usuario1 (remitente de prueba)
crear_usuario "usuario1" "Alumno123"

# Creación de usuario2 (destinatario de prueba)
crear_usuario "usuario2" "Alumno123"

echo "=== CUENTAS Y BUZONES CREADOS EXITOSAMENTE ==="
echo "Cuentas disponibles:"
echo "1. usuario1@empresa.local (Clave: Alumno123)"
echo "2. usuario2@empresa.local (Clave: Alumno123)"

