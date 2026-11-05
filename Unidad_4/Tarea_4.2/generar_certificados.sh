#!/bin/bash
# ==============================================================================
# Script para generar Certificado SSL/TLS Autofirmado con Subject Alternative Names (SAN)
# ==============================================================================
set -e

echo "[*] Generando clave privada y certificado SSL para empresa.local..."

# Crear archivo de configuración OpenSSL temporal con SAN
cat <<EOF > /tmp/openssl_san.cnf
[req]
default_bits = 2048
prompt = no
default_md = sha256
distinguished_name = dn
req_extensions = req_ext

[dn]
C = MX
ST = Estado
L = Ciudad
O = Empresa Corporativa S.A.
OU = TI y Servidores
CN = empresa.local

[req_ext]
subjectAltName = @alt_names

[alt_names]
DNS.1 = empresa.local
DNS.2 = www.empresa.local
DNS.3 = mail.empresa.local
DNS.4 = ftp.empresa.local
IP.1 = 192.168.50.10
EOF

# Generar clave privada y certificado válido por 365 días
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/empresa.local.key \
  -out /etc/ssl/certs/empresa.local.crt \
  -config /tmp/openssl_san.cnf -extensions req_ext

# Proteger la clave privada
sudo chmod 600 /etc/ssl/private/empresa.local.key
sudo chmod 644 /etc/ssl/certs/empresa.local.crt

rm -f /tmp/openssl_san.cnf

echo "[+] Certificado y clave generados exitosamente en /etc/ssl/certs/ y /etc/ssl/private/"

