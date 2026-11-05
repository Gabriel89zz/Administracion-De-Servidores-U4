# Memoria Técnica: Tarea 4.1 – Servicios de Infraestructura Base (DHCP y DNS BIND9)

**Materia:** Administración de Servidores  
**Unidad:** 4 – Servidores DNS, Web, FTP, DHCP, Correos  
**Alumno:** [Nombre del Alumno]  
**Docente:** [Nombre del Docente]  
**Fecha:** Noviembre 2026  
**Sistema Operativo del Servidor:** Debian GNU/Linux 12 (Server)  
**Sistema Operativo Cliente:** Estación de Trabajo Cliente (Linux Desktop / Windows)  

---

## 1. Introducción y Objetivos

En toda infraestructura de red corporativa, la resolución de nombres de dominio y la asignación automatizada de direccionamiento lógico constituyen los dos pilares sobre los cuales operan los demás servicios (web, correo, bases de datos y almacenamiento). 

El objetivo de esta práctica es diseñar, implementar, securizar y documentar:
1. Un servidor de direccionamiento dinámico **ISC-DHCP-Server**, configurado con rangos dinámicos, exclusiones de seguridad, tiempos de concesión (*lease times*) y reservas estáticas basadas en la dirección física (MAC) de la tarjeta de red.
2. Un servidor de nombres de dominio **BIND9**, configurado como servidor autoritativo para la zona directa `empresa.local` y su correspondiente zona inversa, implementando registros A, CNAME, PTR, MX y reenviadores recursivos (*forwarders*) hacia servidores públicos de internet.

---

## 2. Plan de Direccionamiento y Arquitectura de Red

La topología del laboratorio opera bajo un esquema cliente/servidor dentro de un entorno virtualizado aislado en VMware Workstation:

```mermaid
flowchart TD
    subgraph RedLaboratorio["Red Corporativa Aislada: 192.168.50.0/24 (LAN Segment / Host-Only)"]
        Server["Debian GNU/Linux Server\nIP Fija: 192.168.50.10/24\nServicios: BIND9 DNS + ISC-DHCP-Server"]
        ClienteDinamico["Estación Cliente (DHCP Dinámico)\nIP Asignada: 192.168.50.100 - 192.168.50.149\nGateway: 192.168.50.1 | DNS: 192.168.50.10"]
        ClienteReservado["Estación Cliente VIP (Reserva MAC)\nMAC: [Dirección MAC Cliente]\nIP Fija DHCP: 192.168.50.25"]
    end
    Server <-->|Solicitud y Oferta DHCP / Consultas DNS UDP 53| ClienteDinamico
    Server <-->|Concesión Fija por MAC| ClienteReservado
    Server -.->|Forwarders UDP 53 (8.8.8.8 / 1.1.1.1)| Internet((Internet))
```

### Tabla del Plan de Direccionamiento IP Corporativo

| Parámetro | Valor de Configuración | Descripción Técnica |
| :--- | :--- | :--- |
| **Identificador de Red** | `192.168.50.0` | Segmento de red privado Clase C. |
| **Máscara de Subred** | `255.255.255.0` (`/24`) | Prefijo de 24 bits, permitiendo 254 hosts útiles. |
| **Puerta de Enlace (Gateway)** | `192.168.50.1` | Dirección de salida hacia otros segmentos. |
| **IP Servidor Debian (DNS/DHCP)** | `192.168.50.10` | IP estática asignada a la interfaz del laboratorio. |
| **Rango Dinámico DHCP 1** | `192.168.50.100` – `192.168.50.149` | 50 direcciones para clientes dinámicos de oficina. |
| **Rango Excluido** | `192.168.50.150` – `192.168.50.160` | Rango protegido reservado para impresoras de red. |
| **Rango Dinámico DHCP 2** | `192.168.50.161` – `192.168.50.200` | 40 direcciones para dispositivos móviles y visitantes. |
| **Reserva Estática (MAC)** | `192.168.50.25` | Dirección IP dedicada para la estación cliente VIP. |
| **Tiempo de Concesión (Default)**| `600 segundos` (10 min) | Tiempo de renovación para pruebas rápidas de laboratorio. |
| **Tiempo de Concesión (Máximo)**| `7200 segundos` (2 horas) | Límite máximo de retención de IP sin renovación. |
| **Reenviadores DNS** | `8.8.8.8`, `1.1.1.1` | Servidores públicos para resolución externa. |

---

## 3. Implementación y Archivos de Configuración Comentados

### 3.1 Configuración del Servicio DHCP (`/etc/dhcp/dhcpd.conf`)
```text
# Declaración de opciones globales propagadas a los clientes
option domain-name "empresa.local";
option domain-name-servers 192.168.50.10;
default-lease-time 600;
max-lease-time 7200;
authoritative;

# Subred corporativa con división de rangos y exclusión de IPs
subnet 192.168.50.0 netmask 255.255.255.0 {
    range 192.168.50.100 192.168.50.149; # Rango 1
    range 192.168.50.161 192.168.50.200; # Rango 2 (Excluye .150 al .160)
    option routers 192.168.50.1;
    option broadcast-address 192.168.50.255;
}

# Reserva estática vinculada a la dirección física (MAC)
host cliente-vip {
    hardware ethernet 00:0c:29:xx:xx:xx; # MAC de la interfaz de red del cliente
    fixed-address 192.168.50.25;
    option host-name "cliente-vip.empresa.local";
}
```

### 3.2 Opciones Globales de BIND9 (`/etc/bind/named.conf.options`)
```text
options {
    directory "/var/cache/bind";
    allow-query { localhost; 192.168.50.0/24; };
    allow-recursion { localhost; 192.168.50.0/24; };
    forwarders {
        8.8.8.8;
        1.1.1.1;
    };
    forward first;
    dnssec-validation auto;
    listen-on port 53 { 127.0.0.1; 192.168.50.10; };
};
```

### 3.3 Declaración de Zonas (`/etc/bind/named.conf.local`)
```text
zone "empresa.local" {
    type master;
    file "/etc/bind/zones/db.empresa.local";
    allow-transfer { none; };
};

zone "50.168.192.in-addr.arpa" {
    type master;
    file "/etc/bind/zones/db.192.168.50";
    allow-transfer { none; };
};
```

### 3.4 Archivo de Zona Directa (`/etc/bind/zones/db.empresa.local`)
```text
$TTL    86400
@       IN      SOA     ns1.empresa.local. admin.empresa.local. (
                        2026110501      ; Serial
                        3600            ; Refresh (1h)
                        1800            ; Retry (30m)
                        1209600         ; Expire (2w)
                        86400 )         ; Negative Cache TTL
@       IN      NS      ns1.empresa.local.
@       IN      A       192.168.50.10
ns1     IN      A       192.168.50.10
server  IN      A       192.168.50.10
www     IN      A       192.168.50.10
mail    IN      A       192.168.50.10
ftp     IN      A       192.168.50.10
web     IN      CNAME   www.empresa.local.
@       IN      MX  10  mail.empresa.local.
@       IN      TXT     "v=spf1 mx ip4:192.168.50.10 ~all"
```

### 3.5 Archivo de Zona Inversa (`/etc/bind/zones/db.192.168.50`)
```text
$TTL    86400
@       IN      SOA     ns1.empresa.local. admin.empresa.local. (
                        2026110501 3600 1800 1209600 86400 )
@       IN      NS      ns1.empresa.local.
10      IN      PTR     ns1.empresa.local.
10      IN      PTR     server.empresa.local.
10      IN      PTR     www.empresa.local.
10      IN      PTR     mail.empresa.local.
10      IN      PTR     ftp.empresa.local.
```

---

## 4. Guía de Evidencias Fotográficas Requeridas para el PDF

> **Instrucciones para el Alumno:**  
> A continuación se detallan las **6 capturas exactas** que debes tomar para pegarlas en este apartado de tu reporte técnico antes de exportar a PDF:

---

### 📸 Evidencia 1: Validación de Sintaxis de BIND9 y DHCP en Debian Server
* **Comando a ejecutar en Debian:**
  ```bash
  sudo named-checkconf
  sudo named-checkzone empresa.local /etc/bind/zones/db.empresa.local
  sudo named-checkzone 50.168.192.in-addr.arpa /etc/bind/zones/db.192.168.50
  sudo dhcpd -t -cf /etc/dhcp/dhcpd.conf
  ```
* **Qué debe verse:** La respuesta `OK` con el número de serie de ambas zonas DNS y la ausencia de errores en la verificación del archivo de DHCP.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 1: Verificación de sintaxis de BIND9 y DHCP]`  
  > *Figura 1.1: Comprobación de integridad de los archivos de zona directa, inversa y configuración dhcpd.*

---

### 📸 Evidencia 2: Solicitud y Concesión de IP Dinámica en la Estación Cliente
* **Comando a ejecutar en el Cliente:**
  - En Linux: `sudo dhclient -r && sudo dhclient -v` seguido de `ip a`
  - En Windows: `ipconfig /release && ipconfig /renew` seguido de `ipconfig /all`
* **Qué debe verse:** La dirección IP asignada dentro del rango `192.168.50.100 - 192.168.50.149`, con máscara `255.255.255.0`, Gateway `192.168.50.1` y DNS `192.168.50.10`.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 2: Asignación dinámica de IP por DHCP en el cliente]`  
  > *Figura 1.2: Parámetros de red recibidos automáticamente desde el servidor Debian.*

---

### 📸 Evidencia 3: Registro de Logs en Tiempo Real de DHCP (Proceso DORA)
* **Comando a ejecutar en Debian:**
  ```bash
  sudo tail -n 25 /var/log/syslog | grep dhcpd
  ```
* **Qué debe verse:** Las cuatro fases del protocolo: `DHCPDISCOVER`, `DHCPOFFER`, `DHCPREQUEST` y `DHCPACK` dirigidas a la dirección MAC del cliente.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 3: Logs de transacción DHCP en /var/log/syslog]`  
  > *Figura 1.3: Trazabilidad de la concesión de dirección IP registrada por el demonio dhcpd.*

---

### 📸 Evidencia 4: Demostración de la Reserva Estática por Dirección MAC
* **Comando a ejecutar en Debian:** Mostrar el bloque `host cliente-vip` de `/etc/dhcp/dhcpd.conf`.
* **Comando a ejecutar en el Cliente:** Renovar la concesión (`dhclient -v` o `ipconfig /renew`) y mostrar la IP obtenida.
* **Qué debe verse:** La coincidencia exacta entre la dirección física MAC configurada y la entrega garantizada de la IP fija `192.168.50.25`.
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 4: Comprobación de reserva estática por dirección MAC]`  
  > *Figura 1.4: Asignación de la IP 192.168.50.25 a la interfaz física específica del cliente.*

---

### 📸 Evidencia 5: Resolución de Nombres Directa con nslookup y dig
* **Comandos a ejecutar en el Cliente:**
  ```bash
  nslookup www.empresa.local 192.168.50.10
  nslookup web.empresa.local 192.168.50.10
  dig @192.168.50.10 empresa.local MX
  ```
* **Qué debe verse:**
  - `www.empresa.local` resolviendo a `192.168.50.10`.
  - El alias CNAME `web.empresa.local` apuntando canónicamente a `www.empresa.local`.
  - El registro MX devolviendo `10 mail.empresa.local.`
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 5: Resolución directa de registros A, CNAME y MX]`  
  > *Figura 1.5: Respuestas DNS autoritativas emitidas por el servidor BIND9 local.*

---

### 📸 Evidencia 6: Resolución Inversa (PTR) y Reenvío Externo (Forwarder)
* **Comandos a ejecutar en el Cliente:**
  ```bash
  nslookup 192.168.50.10 192.168.50.10
  nslookup google.com 192.168.50.10
  ```
* **Qué debe verse:**
  - La IP `192.168.50.10` traduciéndose de regreso a `ns1.empresa.local`.
  - La consulta a `google.com` resolviendo correctamente gracias a los reenviadores públicos (`8.8.8.8` y `1.1.1.1`).
* **Espacio para la captura en tu documento:**
  > `[INSERTAR AQUÍ CAPTURA 6: Resolución inversa y prueba de reenviadores forwarders]`  
  > *Figura 1.6: Traducción de dirección IP a nombre y consulta recursiva hacia internet.*

---

## 5. Conclusiones Técnicas

La implementación de los servicios base de infraestructura ha demostrado la interdependencia crítica entre el protocolo de configuración dinámica de host (DHCP) y el sistema de nombres de dominio (DNS):
1. **Eficiencia en la Administración:** El servidor DHCP elimina la intervención manual y los posibles conflictos por IP duplicadas, asegurando además la exclusión de direcciones para dispositivos de red fijos.
2. **Robustez en la Resolución Local:** El servidor BIND9 no solo resuelve de forma autoritativa la red corporativa privada `empresa.local`, sino que mantiene la continuidad de navegación hacia la red exterior mediante políticas de reenviadores.
3. **Validación del Esquema Cliente/Servidor:** Ambas tecnologías fueron probadas y validadas con éxito desde la estación cliente, cumpliendo íntegramente con los criterios de desempeño exigidos en la Tarea 4.1.

---

## 6. Enlaces de Entrega

* **Repositorio Público de GitHub:** `https://github.com/[Tu_Usuario]/[Tu_Repositorio]/tree/main/Unidad_4/Tarea_4.1`
* **Video Demostrativo en YouTube:** `https://youtu.be/[ID_DE_TU_VIDEO]`

