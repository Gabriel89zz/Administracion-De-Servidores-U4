# Manual de Pruebas: Tarea 4.1 (Servidor DHCP y DNS BIND9)

Este manual contiene los pasos y comandos exactos para verificar la correcta implementación del servidor DHCP y DNS en **Debian Server**, utilizando como estación cliente el adaptador **VMware Network Adapter VMnet1** de tu máquina física Windows (o una VM cliente).

---

## 1. Verificación Inicial en el Servidor Debian

Antes de realizar pruebas desde el cliente, debemos asegurarnos de que la sintaxis de las configuraciones sea correcta y que los servicios estén activos.

### Paso 1.1: Validar sintaxis de DNS BIND9
Ejecuta en la terminal de Debian:
```bash
# Validar archivo principal de opciones y sintaxis general
sudo named-checkconf

# Validar archivo de zona directa
sudo named-checkzone empresa.local /etc/bind/zones/db.empresa.local

# Validar archivo de zona inversa
sudo named-checkzone 50.168.192.in-addr.arpa /etc/bind/zones/db.192.168.50
```
> **Resultado esperado:** Ambos comandos de zona deben devolver `OK` con el número de serie cargado (ej. `loaded serial 2026110501`). Si no hay salida en `named-checkconf`, significa que no hay errores.

### Paso 1.2: Validar sintaxis de DHCP
```bash
sudo dhcpd -t -cf /etc/dhcp/dhcpd.conf
```
> **Resultado esperado:** No debe marcar errores de sintaxis en ninguna línea.

### Paso 1.3: Reiniciar y comprobar estado de los servicios
```bash
sudo systemctl restart bind9
sudo systemctl status bind9 --no-pager

sudo systemctl restart isc-dhcp-server
sudo systemctl status isc-dhcp-server --no-pager
```
> **Resultado esperado:** Ambos servicios deben mostrar `active (running)` en color verde.

---

## 2. Pruebas de Funcionamiento desde el Cliente (Windows con VMnet1)

Abre **PowerShell** o **Símbolo del sistema (CMD)** como Administrador en tu Windows.

### Paso 2.1: Comprobar asignación de IP por DHCP (Rango Dinámico)
Libera y renueva la concesión IP del adaptador virtual de VMware:

```powershell
# En PowerShell:
ipconfig /release "VMware Network Adapter VMnet1"
ipconfig /renew "VMware Network Adapter VMnet1"
ipconfig /all
```

> **Verificación:**
> - Busca en la salida la sección `Adaptador de Ethernet VMware Network Adapter VMnet1`.
> - **Dirección IPv4:** Debe estar dentro del rango dinámico configurado: entre `192.168.50.100` y `192.168.50.149` (o entre `161` y `200`).
> - **Máscara de subred:** `255.255.255.0`.
> - **Puerta de enlace predeterminada:** `192.168.50.1`.
> - **Servidores DNS:** `192.168.50.10`.
> - **Servidor DHCP:** `192.168.50.10`.

### Paso 2.2: Monitoreo en vivo de la concesión DHCP en el Servidor Debian
Mientras ejecutas el `/renew` en Windows, mantén este comando abierto en Debian:
```bash
sudo tail -f /var/log/syslog | grep dhcpd
```
> **Resultado visible en pantalla:** Verás la secuencia DORA:
> 1. `DHCPDISCOVER from <MAC> via ens37`
> 2. `DHCPOFFER on 192.168.50.10x to <MAC> via ens37`
> 3. `DHCPREQUEST for 192.168.50.10x from <MAC> via ens37`
> 4. `DHCPACK on 192.168.50.10x to <MAC> via ens37`

---

## 3. Prueba de Reserva Estática por MAC

### Paso 3.1: Obtener la dirección física (MAC) del adaptador cliente
En Windows ejecuta:
```powershell
Get-NetAdapter -Name "*VMnet1*" | Select-Object Name, MacAddress
```
Copia el valor de `MacAddress` (ejemplo: `00-50-56-C0-00-01`).

### Paso 3.2: Registrar la MAC en Debian
Edita `/etc/dhcp/dhcpd.conf`:
```bash
sudo nano /etc/dhcp/dhcpd.conf
```
En el bloque `host cliente-vip`, reemplaza los guiones por dos puntos:
```text
host cliente-vip {
    hardware ethernet 00:50:56:c0:00:01;
    fixed-address 192.168.50.25;
}
```
Reinicia el servicio:
```bash
sudo systemctl restart isc-dhcp-server
```

### Paso 3.3: Renovar IP en Windows
```powershell
ipconfig /release "VMware Network Adapter VMnet1"
ipconfig /renew "VMware Network Adapter VMnet1"
```
> **Resultado esperado:** La dirección IPv4 asignada a VMnet1 ahora será exactamente: **`192.168.50.25`**.

---

## 4. Pruebas de Resolución DNS (BIND9)

Desde la terminal de Windows ejecuta las siguientes consultas contra tu servidor Debian (`192.168.50.10`):

### 4.1 Resolución Directa de Registros Tipo A
```cmd
nslookup www.empresa.local 192.168.50.10
nslookup mail.empresa.local 192.168.50.10
nslookup ftp.empresa.local 192.168.50.10
```
> **Resultado esperado:** Cada consulta debe responder con `Dirección: 192.168.50.10`.

### 4.2 Resolución de Alias CNAME
```cmd
nslookup web.empresa.local 192.168.50.10
```
> **Resultado esperado:** Devuelve el alias indicando: `web.empresa.local canonical name = www.empresa.local`.

### 4.3 Resolución de Registro MX (Servidor de Correo)
```cmd
nslookup -type=mx empresa.local 192.168.50.10
```
> **Resultado esperado:** `empresa.local MX preference = 10, mail exchanger = mail.empresa.local`.

### 4.4 Resolución Inversa (Registro PTR)
```cmd
nslookup 192.168.50.10 192.168.50.10
```
> **Resultado esperado:** `Nombre: ns1.empresa.local` o `server.empresa.local`.

### 4.5 Comprobación de Reenviadores (Forwarders hacia Internet)
```cmd
nslookup google.com 192.168.50.10
```
> **Resultado esperado:** El servidor local reenvía la consulta a `8.8.8.8` y devuelve la IP pública real de Google.

