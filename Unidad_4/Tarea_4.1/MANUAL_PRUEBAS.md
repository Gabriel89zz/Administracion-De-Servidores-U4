# Manual de Pruebas: Tarea 4.1 (Servidor DHCP y DNS BIND9)

Este manual contiene los pasos y comandos exactos para verificar la correcta implementación del servidor DHCP y DNS en **Debian Server**, probado desde una estación cliente con **Debian XFCE** (o Windows).

---

## 1. Verificación Inicial en Debian Server

Antes de realizar pruebas desde el cliente, validamos la integridad y sintaxis en el servidor:

```bash
# 1.1 Validar sintaxis general de BIND9
sudo named-checkconf

# 1.2 Validar archivo de zona directa
sudo named-checkzone empresa.local /etc/bind/zones/db.empresa.local

# 1.3 Validar archivo de zona inversa
sudo named-checkzone 50.168.192.in-addr.arpa /etc/bind/zones/db.192.168.50

# 1.4 Validar sintaxis de DHCP
sudo dhcpd -t -cf /etc/dhcp/dhcpd.conf

# 1.5 Reiniciar y comprobar estado activo de ambos servicios
sudo systemctl restart bind9 && sudo systemctl status bind9 --no-pager
sudo systemctl restart isc-dhcp-server && sudo systemctl status isc-dhcp-server --no-pager
```
> **Resultado esperado:** Ambas zonas deben responder `OK` con el número de serie cargado y ambos servicios deben figurar en estado `active (running)`.

---

## 2. Pruebas de Asignación de IP Dinámica (DHCP)

### Paso 2.1: En el Servidor Debian (Monitoreo en Vivo de Logs)
Abre una terminal en Debian Server y ejecuta:
```bash
sudo tail -f /var/log/syslog | grep dhcpd
```

### Paso 2.2: En la Estación Cliente (Solicitud de IP)

#### 🐧 Opción A: En Cliente Linux (Debian XFCE)
Abre la terminal en Debian XFCE:
```bash
# Liberar la IP actual
sudo dhclient -r

# Solicitar nueva IP por DHCP con reporte detallado (-v)
sudo dhclient -v

# Verificar la IP, máscara y estado de la interfaz
ip a

# Verificar los servidores DNS entregados por DHCP
cat /etc/resolv.conf

# Verificar la puerta de enlace (Gateway)
ip route
```

#### 🪟 Opción B: En Cliente Windows (CMD o PowerShell)
```powershell
ipconfig /release
ipconfig /renew
ipconfig /all
```

> **Verificación del Resultado:**
> - En el **Servidor**, el log mostrará la secuencia DORA: `DHCPDISCOVER` -> `DHCPOFFER` -> `DHCPREQUEST` -> `DHCPACK`.
> - En el **Cliente**, la IP asignada debe estar dentro del rango: `192.168.50.100` a `192.168.50.149`.
> - La puerta de enlace debe ser: `192.168.50.1`.
> - El servidor DNS recibido debe ser: `192.168.50.10`.

---

## 3. Prueba de Reserva Estática por MAC

### Paso 3.1: Obtener la Dirección MAC del Cliente
* **En Debian XFCE (Linux):**
  ```bash
  ip link show
  ```
  Busca tu interfaz (ej. `ens33` o `eth0`) y copia el valor de `link/ether` (ejemplo: `00:0c:29:4f:8a:12`).
* **En Windows:**
  ```cmd
  getmac /v
  ```

### Paso 3.2: Configurar la Reserva en Debian Server
Edita `/etc/dhcp/dhcpd.conf`:
```bash
sudo nano /etc/dhcp/dhcpd.conf
```
En el bloque `host cliente-vip`, coloca la dirección MAC que obtuviste:
```text
host cliente-vip {
    hardware ethernet 00:0c:29:4f:8a:12;  # <-- Tu MAC real
    fixed-address 192.168.50.25;
}
```
Reinicia el servicio DHCP:
```bash
sudo systemctl restart isc-dhcp-server
```

### Paso 3.3: Renovar IP en la Estación Cliente
* **En Debian XFCE (Linux):**
  ```bash
  sudo dhclient -r && sudo dhclient -v
  ip a
  ```
* **En Windows:**
  ```powershell
  ipconfig /release && ipconfig /renew
  ```
> **Resultado esperado:** El servidor reconoce la MAC y le entrega obligatoriamente la IP: **`192.168.50.25`**.

---

## 4. Pruebas de Resolución DNS BIND9

Ejecuta estas pruebas desde la terminal de tu estación cliente:

### 4.1 Resolución Directa de Registros Tipo A
* **En Linux (Debian XFCE):**
  ```bash
  nslookup www.empresa.local 192.168.50.10
  dig @192.168.50.10 mail.empresa.local A
  ```
* **En Windows:**
  ```cmd
  nslookup www.empresa.local 192.168.50.10
  nslookup mail.empresa.local 192.168.50.10
  ```
> **Resultado:** Responde con la IP `192.168.50.10`.

### 4.2 Resolución de Alias Canónico CNAME
* **En Linux / Windows:**
  ```bash
  nslookup web.empresa.local 192.168.50.10
  ```
> **Resultado:** Muestra: `web.empresa.local canonical name = www.empresa.local` y dirección `192.168.50.10`.

### 4.3 Resolución de Registro MX (Servidor de Correo)
* **En Linux (Debian XFCE):**
  ```bash
  dig @192.168.50.10 empresa.local MX +short
  # O con nslookup:
  nslookup -query=mx empresa.local 192.168.50.10
  ```
* **En Windows:**
  ```cmd
  nslookup -type=mx empresa.local 192.168.50.10
  ```
> **Resultado:** `10 mail.empresa.local.`

### 4.4 Resolución Inversa (Registro PTR)
* **En Linux (Debian XFCE):**
  ```bash
  dig @192.168.50.10 -x 192.168.50.10 +short
  # O con nslookup:
  nslookup 192.168.50.10 192.168.50.10
  ```
* **En Windows:**
  ```cmd
  nslookup 192.168.50.10 192.168.50.10
  ```
> **Resultado:** Devuelve el nombre de host: `ns1.empresa.local` o `server.empresa.local`.

### 4.5 Resolución de Reenviadores Externos (Forwarders)
* **En Linux / Windows:**
  ```bash
  nslookup google.com 192.168.50.10
  ```
> **Resultado:** Resuelve la IP pública de Google gracias a los reenviadores (`8.8.8.8` / `1.1.1.1`).
