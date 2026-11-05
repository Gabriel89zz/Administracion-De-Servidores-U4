# Tarea 4.1: Servicios de Infraestructura Base – Servidor DHCP y DNS

Este repositorio contiene los archivos de configuración comentados, manual de pruebas y guion demostrativo para la implementación de los servicios DHCP y DNS sobre **Debian Server** para una red corporativa.

---

## Contenido de la Carpeta

| Archivo | Descripción |
| :--- | :--- |
| **`dhcpd.conf`** | Configuración de ISC-DHCP-Server: rangos dinámicos, exclusiones, lease times y reserva por MAC. |
| **`isc-dhcp-server`** | Archivo para `/etc/default/isc-dhcp-server` especificando la interfaz de escucha. |
| **`named.conf.options`** | Configuración de BIND9: forwarders (Google/Cloudflare), recursión y puertos de escucha. |
| **`named.conf.local`** | Declaración de zonas maestra directa (`empresa.local`) e inversa (`50.168.192.in-addr.arpa`). |
| **`db.empresa.local`** | Archivo de zona directa con registros SOA, NS, A (ns1, www, mail, ftp), CNAME, MX y SPF (TXT). |
| **`db.192.168.50`** | Archivo de zona inversa con registros SOA, NS y punteros PTR. |
| **`MANUAL_PRUEBAS.md`** | Guía paso a paso con comandos exactos para verificar DHCP y DNS desde Debian y Windows. |
| **`GUION_VIDEO.md`** | Guion detallado para la grabación del video en YouTube con cámara web en la esquina inferior derecha. |

---

## Parámetros de Red Corporativa

- **Red:** `192.168.50.0/24`
- **Gateway:** `192.168.50.1`
- **Servidor Debian (DHCP & DNS):** `192.168.50.10`
- **Rango Dinámico:** `192.168.50.100` a `192.168.50.149` y `192.168.50.161` a `192.168.50.200`
- **Rango Excluido:** `192.168.50.150` a `192.168.50.160`
- **Reserva Estática (MAC):** `192.168.50.25`

